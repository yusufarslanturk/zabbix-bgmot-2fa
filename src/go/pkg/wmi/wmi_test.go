/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

package wmi

import (
	"encoding/json"
	"errors"
	"fmt"
	"runtime"
	"testing"

	"github.com/go-ole/go-ole"
	"github.com/go-ole/go-ole/oleutil"
)

func oleInt64(item *ole.IDispatch, prop string) (int64, error) {
	v, err := oleutil.GetProperty(item, prop)
	if err != nil {
		return 0, err
	}
	defer v.Clear()

	i := int64(v.Val)
	return i, nil
}

func firstProp(disp *ole.IDispatch) (v *ole.VARIANT, err error) {
	newEnum, err := disp.GetProperty("_NewEnum")
	if err != nil {
		return
	}
	defer newEnum.Clear()

	enum, err := newEnum.ToIUnknown().IEnumVARIANT(ole.IID_IEnumVariant)
	if err != nil {
		return
	}
	defer enum.Release()

	item, length, err := enum.Next(1)
	if err != nil {
		return
	}
	if length == 0 {
		return
	}
	return &item, nil
}

func performQuery2(namespace string, query string, single bool) (ret interface{}, err error) {
	runtime.LockOSThread()
	defer runtime.UnlockOSThread()
	if oleErr := ole.CoInitializeEx(0, ole.COINIT_MULTITHREADED); oleErr != nil {
		oleCode := oleErr.(*ole.OleError).Code()
		if oleCode != ole.S_OK && oleCode != S_FALSE {
			return nil, oleErr
		}
	}
	defer ole.CoUninitialize()

	var unknown *ole.IUnknown
	if unknown, err = oleutil.CreateObject("WbemScripting.SWbemLocator"); err != nil {
		return
	}
	if unknown == nil {
		return nil, errors.New("Cannot create SWbemLocator object.")
	}
	defer unknown.Release()

	var disp *ole.IDispatch
	if disp, err = unknown.QueryInterface(ole.IID_IDispatch); err != nil {
		return
	}
	defer disp.Release()

	var raw *ole.VARIANT
	if raw, err = oleutil.CallMethod(disp, "ConnectServer", nil, namespace); err != nil {
		return
	}
	service := raw.ToIDispatch()
	defer raw.Clear()

	if raw, err = oleutil.CallMethod(service, "ExecQuery", query); err != nil {
		return
	}
	result := raw.ToIDispatch()
	defer raw.Clear()

	var count int64
	if count, err = oleInt64(result, "Count"); err != nil {
		return
	}
	if count == 0 {
		return
	}

	row, err := firstProp(result)
	if err != nil {
		return
	}

	raw, err = row.Value().(*ole.IDispatch).GetProperty("Properties_")
	if err != nil {
		return nil, err
	}
	defer raw.Clear()

	props := raw.ToIDispatch()
	defer props.Release()

	oleutil.ForEach(props, func(v *ole.VARIANT) error {
		disp := v.ToIDispatch()
		defer disp.Release()

		name, err := disp.GetProperty("Name")
		if err != nil {
			return err
		}
		defer name.Clear()

		value, err := disp.GetProperty("Value")
		if err != nil {
			return err
		}
		defer value.Clear()

		sname, _ := name.Value().(string)
		svalue, _ := value.Value().(string)

		fmt.Println("???", sname, svalue, value.Value())
		return nil
	})

	fmt.Printf("ROW: %+v (%T)\n", row.Value(), row.Value())
	val, err := oleutil.GetProperty(row.Value().(*ole.IDispatch), "Name")
	if err != nil {
		return
	}

	fmt.Printf("Val: %+v\n", val.ToString())
	fmt.Printf("Val: %+v\n", val)

	disp, ok := row.Value().(*ole.IDispatch)
	if !ok {
		return
	}

	col, err := firstProp(disp)

	fmt.Printf("COL: %+v\n", col)

	return
}

func TestWmi(t *testing.T) {
	ret, err := QueryValue(`root\cimv2`, `select deviceid from Win32_DiskDrive where Name like '%PHYSICALDRIVE%'`)
	if err != nil {
		t.Errorf("Query fail: %s", err)
		t.Fail()
	}

	m := ret

	b, _ := json.Marshal(m)
	fmt.Println(string(b))

	t.Fail()
}
