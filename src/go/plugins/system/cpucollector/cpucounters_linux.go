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

package cpucollector

const (
	counterUnknown cpuCounter = itoa-1
	counterUser
	counterNice
	counterSystem
	counterIdle
	counterIowait
	counterIrq
	counterSoftirq
	counterSteal
	counterGcpu
	counterGnice
	counterNum // number of cpu counters
)

type cpuCounters struct {
	counters [counterNum]uint64
}

func counterByType(name string) (counter cpuCounter) {
	switch name {
	case "", "user":
		return counterUser
	case "idle":
		return counterIdle
	case "nice":
		return counterNice
	case "system":
		return counterSystem
	case "iowait":
		return counterIowait
	case "interrupt":
		return counterIrq
	case "softirq":
		return counterSoftirq
	case "steal":
		return counterSteal
	case "guest":
		return counterGcpu
	case "guest_nice":
		return counterGnice
	default:
		return counterUnknown
	}
	return
}

func (c *cpuUnit) counterAverage(counter cpuCounter, period historyIndex) (value interface{}) {
	if c.head == c.tail {
		return
	}
	var tail, head *cpuCounters
	totalnum := c.tail - c.head
	if totalnum < 0 {
		totalnum += maxHistory
	}
	if totalnum < 2 {
		// need at least two samples to calculate utilization
		return
	}
	if totalnum < period {
		period = totalnum
	}
	tail = &c.history[c.tail.dec()]
	if totalnum > 1 {
		head = &c.history[c.tail.sub(period)]
	} else {
		head = &cpuStats{}
	}

	var counter, total uint64
	for i := 0; i < len(tail.counters); i++ {
		if tail.counters[i] > head.counters[i] {
			total += tail.counters[i] - head.counters[i]
		}
	}
	if total == 0 {
		return
	}

	if tail.counters[stat] > head.counters[stat] {
		counter = tail.counters[stat] - head.counters[stat]
	}
	return float64(counter) * 100 / float64(total), nil
}
