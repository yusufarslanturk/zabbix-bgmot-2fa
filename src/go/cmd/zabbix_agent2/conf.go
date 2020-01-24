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

package main

import (
	"fmt"
	"time"

	"zabbix.com/internal/agent"
	"zabbix.com/internal/agent/scheduler"
	"zabbix.com/internal/agent/serverconnector"
	"zabbix.com/pkg/log"
)

const hostNameLen = 128

func ConfigDefault(taskManager scheduler.Scheduler, options *agent.AgentOptions) error {
	var err error

	if len(options.Hostname) == 0 {
		var hostnameItem string

		if len(options.HostnameItem) == 0 {
			hostnameItem = "system.hostname"
		} else {
			hostnameItem = options.HostnameItem
		}

		options.Hostname, err = taskManager.PerformTask(hostnameItem, time.Second*time.Duration(options.Timeout))
		if err != nil {
			if len(options.HostnameItem) == 0 {
				return fmt.Errorf("cannot get system hostname using \"%s\" item as default for \"HostnameItem\" configuration parameter: %s", hostnameItem, err.Error())
			}

			return fmt.Errorf("cannot get system hostname using \"%s\" item specified by \"HostnameItem\" configuration parameter: %s", hostnameItem, err.Error())
		}
		if len(options.Hostname) == 0 {
			return fmt.Errorf("cannot get system hostname using \"%s\" item specified by \"HostnameItem\" configuration parameter: value is empty", hostnameItem)
		}
		if len(options.Hostname) > hostNameLen {
			options.Hostname = options.Hostname[:hostNameLen]
			log.Warningf("the returned value of \"%s\" item specified by \"HostnameItem\" configuration parameter is too long, using first %d characters", hostnameItem, hostNameLen)
		}
		if err = agent.CheckHostname(options.Hostname); err != nil {
			return fmt.Errorf("cannot get system hostname using \"%s\" item specified by \"HostnameItem\" configuration parameter: %s", hostnameItem, err.Error())
		}
	} else {
		if len(options.HostnameItem) != 0 {
			log.Warningf("both \"Hostname\" and \"HostnameItem\" configuration parameter defined, using \"Hostname\"")
		}
	}

	return nil
}

func ConfigValidate(options agent.AgentOptions) error {
	var err error

	if len(options.Hostname) > hostNameLen {
		return fmt.Errorf("the value of \"Hostname\" configuration parameter cannot be longer than %d characters", hostNameLen)
	}
	if err = agent.CheckHostname(options.Hostname); err != nil {
		return fmt.Errorf("invalid \"Hostname\" configuration parameter: %s", err.Error())
	}
	if len(options.HostMetadata) > 0 && len(options.HostMetadata) > serverconnector.HostMetadataLen {
		return fmt.Errorf("the value of \"HostMetadata\" configuration parameter cannot be longer than %d characters", serverconnector.HostMetadataLen)
	}
	if len(options.HostInterface) > serverconnector.HostInterfaceLen {
		return fmt.Errorf("the value of \"HostInterface\" configuration parameter cannot be longer than %d characters", serverconnector.HostInterfaceLen)
	}

	return nil
}
