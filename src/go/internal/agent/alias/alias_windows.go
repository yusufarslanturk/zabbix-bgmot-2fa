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

package alias

import (
	"fmt"
	"regexp"

	"zabbix.com/internal/agent"
)

func (m *Manager) loadPerfCounterAliases(key string, aliases []string) (err error) {
	var re *regexp.Regexp
	if re, err = regexp.Compile(`^([^, ]+) *, *"(.*)" *, *([0-9]+)$`); err != nil {
		return
	}
	for _, data := range aliases {
		s := re.FindStringSubmatch(data)
		if len(s) != 4 {
			return fmt.Errorf(`cannot add performance counter alias "%s"`, data)
		}
		for _, alias := range m.aliases {
			if alias.name == s[1] {
				return fmt.Errorf("failed to add Alias \"%s\": duplicate name", s[1])
			}
		}
		fmt.Printf("ADDING: %s\n", fmt.Sprintf("%s[%s,%s]", key, s[2], s[3]))
		m.aliases = append(m.aliases, keyAlias{name: s[1], key: fmt.Sprintf("%s[%s,%s]", key, s[2], s[3])})
	}
	return nil
}

func (m *Manager) initialize(options *agent.AgentOptions) (err error) {
	if err = m.loadAliases(options.Alias); err != nil {
		return
	}
	if err = m.loadPerfCounterAliases("perf_counter", options.PerfCounter); err != nil {
		return
	}
	if err = m.loadPerfCounterAliases("perf_counter_en", options.PerfCounterEn); err != nil {
		return
	}
	return
}
