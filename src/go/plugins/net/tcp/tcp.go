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

package tcp

import (
	"errors"
	"strconv"

	"zabbix.com/pkg/plugin"
)

// Plugin -
type Plugin struct {
	plugin.Base
}

var impl Plugin

func (p *Plugin) exportSystemTcpListen(params []string) (result interface{}, err error) {
	if len(params) > 1 {
		return nil, errors.New("Too many parameters.")
	}
	if len(params) == 0 || params[0] == "" {
		return nil, errors.New("Invalid first parameter.")
	}
	port, err := strconv.ParseUint(params[0], 10, 16)
	if err != nil {
		return nil, errors.New("Invalid first parameter.")
	}

	return exportSystemTcpListen(uint16(port))
}

// Export -
func (p *Plugin) Export(key string, params []string, ctx plugin.ContextProvider) (result interface{}, err error) {
	switch key {
	case "net.tcp.listen":
		return p.exportSystemTcpListen(params)
	default:
		return nil, plugin.UnsupportedMetricError
	}
}

func init() {
	plugin.RegisterMetrics(&impl, "Tcp",
		"net.tcp.listen", "Checks if this TCP port is in LISTEN state.",
	)
}
