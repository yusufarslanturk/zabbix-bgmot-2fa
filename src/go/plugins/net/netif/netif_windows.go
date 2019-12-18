/*
** Zabbix
** Copyright (C) 2001-2019 Zabbix SIA
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

package netif

import (
	"fmt"
	"unsafe"

	"golang.org/x/sys/windows"
	"zabbix.com/pkg/win32"
)

func getNetStats(networkIf string, statName string, dir dirFlag) (result uint64, err error) {
	err = fmt.Errorf("Unsupported metric.")
	return
}

func getDevList() (result []msgIfDiscovery, err error) {
	var table *win32.MIB_IF_TABLE2
	if table, err = win32.GetIfTable2(); err != nil {
		return
	}

	result = make([]msgIfDiscovery, 0, table.NumEntries)
	rows := (*[1 << 16]win32.MIB_IF_ROW2)(unsafe.Pointer(&table.Table[0]))[:table.NumEntries:table.NumEntries]
	for i := range rows {
		result = append(result, msgIfDiscovery{windows.UTF16ToString(rows[i].Description[:])})
	}
	win32.FreeMibTable(table)

	return
}
