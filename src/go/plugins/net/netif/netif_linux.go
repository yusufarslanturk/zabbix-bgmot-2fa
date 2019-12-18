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
	"bufio"
	"encoding/json"
	"fmt"
	"strconv"
	"strings"
)

var mapNetStatIn = map[string]uint{
	"bytes":      0,
	"packets":    1,
	"errors":     2,
	"dropped":    3,
	"overruns":   4,
	"frame":      5,
	"compressed": 6,
	"multicast":  7,
}

var mapNetStatOut = map[string]uint{
	"bytes":      8,
	"packets":    9,
	"errors":     10,
	"dropped":    11,
	"overruns":   12,
	"collisions": 13,
	"carrier":    14,
	"compressed": 15,
}

func addStatNum(statName string, mapNetStat map[string]uint, statNums *[]uint) error {
	statNum, ok := mapNetStat[statName]

	if !ok {
		return fmt.Errorf("Invalid second parameter.")
	}

	*statNums = append(*statNums, statNum)

	return nil
}

func getNetStats(networkIf string, statName string, dir dirFlag) (result uint64, err error) {
	var statNums []uint

	if dir&dirIn != 0 {
		if err = addStatNum(statName, mapNetStatIn, &statNums); err != nil {
			return
		}
	}

	if dir&dirOut != 0 {
		if err = addStatNum(statName, mapNetStatOut, &statNums); err != nil {
			return
		}
	}

	file, err := stdOs.Open("/proc/net/dev")

	if err != nil {
		return 0, fmt.Errorf("Cannot open /proc/net/dev: %s", err)
	}

	defer file.Close()

	var total uint64
loop:
	for sLines := bufio.NewScanner(file); sLines.Scan(); {
		dev := strings.Split(sLines.Text(), ":")

		if len(dev) > 1 && networkIf == strings.TrimSpace(dev[0]) {
			stats := strings.Fields(dev[1])

			if len(stats) >= 16 {
				for _, statNum := range statNums {
					var res uint64

					if res, err = strconv.ParseUint(stats[statNum], 10, 64); err != nil {
						break loop
					}
					total += res
				}
				return total, nil
			}
			break
		}
	}

	err = fmt.Errorf("Cannot find information for this network interface in /proc/net/dev.")

	return
}

func getDevList() (netInterfaces []msgIfDiscovery, err error) {
	var f stdOs.File
	if file, err = stdOs.Open("/proc/net/dev"); err != nil {
		return
	}
	defer f.Close()

	for sLines := bufio.NewScanner(f); sLines.Scan(); {
		dev := strings.Split(sLines.Text(), ":")
		if len(dev) > 1 {
			netInterfaces = append(netInterfaces, msgIfDiscovery{ strings.TrimSpace(dev[0])})
		}
	}

	return netInterfaces, nil
}
