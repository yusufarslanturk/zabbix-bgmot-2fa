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

package tcpudp

import (
	"errors"
	"fmt"
	"net"
	"strconv"

	"zabbix.com/pkg/plugin"
)

const (
	errorInvalidSecondParam = "Invalid second parameter."
	errorTooManyParams      = "Too many parameters."
	errorUnsupportedMetric  = "Unsupported metric."
)

// Plugin -
type Plugin struct {
	plugin.Base
}

var impl Plugin

func (p *Plugin) exportNetTcpPort(params []string) (result int, err error) {
	var ipStr, portStr string
	var port uint64

	if len(params) > 2 {
		err = errors.New(errorTooManyParams)
		return
	}
	if len(params) < 1 || (len(params) == 1 && len(params[0]) == 0) {
		err = errors.New(errorInvalidSecondParam)
		return
	}

	if len(params) == 1 {
		portStr = params[0]
	} else {
		portStr = params[1]
	}

	if port, err = strconv.ParseUint(portStr, 10, 16); err != nil {
		err = errors.New(errorInvalidSecondParam)
		return
	}

	if params[0] == "" {
		ipStr = fmt.Sprintf("127.0.0.1:%d", port)
	} else {
		ipStr = fmt.Sprintf("%s:%d", params[0], port)
	}

	if _, err := net.Dial("tcp", ipStr); err != nil {
		return 0, nil
	}
	return 1, nil
}

// Export -
func (p *Plugin) Export(key string, params []string, ctx plugin.ContextProvider) (result interface{}, err error) {
	switch key {
	case "net.tcp.port":
		return p.exportNetTcpPort(params)
	default:
		/* SHOULD_NEVER_HAPPEN */
		return nil, errors.New(errorUnsupportedMetric)
	}
}

func init() {
	plugin.RegisterMetrics(&impl, "TCP",
		"net.tcp.port", "Checks if it is possible to make TCP connection to specified port.")
}
