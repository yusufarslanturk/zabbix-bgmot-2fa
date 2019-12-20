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
	"encoding/json"
	"errors"
	"fmt"
	"unsafe"

	"golang.org/x/sys/windows"
	"zabbix.com/pkg/plugin"
	"zabbix.com/pkg/std"
	"zabbix.com/pkg/win32"
)

func (p *Plugin) getNetStats(networkIf string, statName string, dir dirFlag) (result uint64, err error) {
	err = fmt.Errorf("Not implemented.")
	return
}

func (p *Plugin) getDevDiscovery() (devices []msgIfDiscovery, err error) {
	var table *win32.MIB_IF_TABLE2
	if table, err = win32.GetIfTable2(); err != nil {
		return
	}

	devices = make([]msgIfDiscovery, 0, table.NumEntries)
	rows := (*[1 << 16]win32.MIB_IF_ROW2)(unsafe.Pointer(&table.Table[0]))[:table.NumEntries:table.NumEntries]
	for i := range rows {
		devices = append(devices, msgIfDiscovery{windows.UTF16ToString(rows[i].Description[:])})
	}
	win32.FreeMibTable(table)

	return
}

func (p *Plugin) getDevList() (devices string, err error) {
	err = fmt.Errorf("Not implemented.")
	return
}

// Export -
func (p *Plugin) Export(key string, params []string, ctx plugin.ContextProvider) (result interface{}, err error) {
	var direction dirFlag
	var mode string

	switch key {
	case "net.if.discovery":
		if len(params) > 0 {
			return nil, errors.New(errorTooManyParams)
		}
		var devices []msgIfDiscovery
		if devices, err = p.getDevDiscovery(); err != nil {
			return
		}
		var b []byte
		if b, err = json.Marshal(devices); err != nil {
			return
		}
		return string(b), nil
	case "net.if.list":
		if len(params) > 1 {
			return nil, errors.New(errorTooManyParams)
		}
		return p.getDevList()
	case "net.if.in":
		direction = dirIn
	case "net.if.out":
		direction = dirOut
	case "net.if.total":
		direction = dirIn | dirOut
	default:
		/* SHOULD_NEVER_HAPPEN */
		return nil, errors.New(errorUnsupportedMetric)
	}

	if len(params) < 1 || params[0] == "" {
		return nil, errors.New(errorEmptyIfName)
	}

	if len(params) > 2 {
		return nil, errors.New(errorTooManyParams)
	}

	if len(params) == 2 && params[1] != "" {
		mode = params[1]
	} else {
		mode = "bytes"
	}

	return p.getNetStats(params[0], mode, direction)
}

func init() {
	stdOs = std.NewOs()

	plugin.RegisterMetrics(&impl, "NetIf",
		"net.if.list", "Returns a list of network interfaces in text format.",
		"net.if.in", "Returns incoming traffic statistics on network interface.",
		"net.if.out", "Returns outgoing traffic statistics on network interface.",
		"net.if.total", "Returns sum of incoming and outgoing traffic statistics on network interface.",
		"net.if.discovery", "Returns list of network interfaces. Used for low-level discovery.")

}
