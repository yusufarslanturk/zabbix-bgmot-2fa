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

import (
	"errors"
	"runtime"

	"zabbix.com/pkg/plugin"
)

// Plugin -
type Plugin struct {
	plugin.Base
	cpus      []*cpuUnit
	collector *pdhCollector
}

func (p *Plugin) numCPU() int {
	return runtime.NumCPU()
}

func (p *Plugin) getCpuLoad(params []string) (result interface{}, err error) {
	period := historyIndex(60)
	switch len(params) {
	case 2: // mode parameter
		if period = periodByMode(params[2]); period < 0 {
			return nil, errors.New("Invalid third parameter.")
		}
		fallthrough
	case 1: // cpu number or all
		if params[0] != "" && params[0] != "all" {
			return nil, errors.New("Invalid first parameter.")
		}
	case 0:
	default:
		return nil, errors.New("Too many parameters.")
	}
	return p.cpus[0].counterAverage(counterLoad, period), nil
}

func (p *Plugin) Collect() (err error) {
	ok, err := p.collector.collect()
	if err != nil || !ok {
		return
	}

	for i, cpu := range p.cpus {
		slot := &cpu.history[cpu.tail]
		cpu.status = cpuStatusOnline
		if i == 0 {
			// gather cpu load into 'total' slot
			slot.load += p.collector.cpuLoad()
		}
		slot.util += p.collector.cpuUtil(i)

		if cpu.tail = cpu.tail.inc(); cpu.tail == cpu.head {
			cpu.head = cpu.head.inc()
		}
		// write the current value into next slot so next time the new value
		// can be added to it resulting in incrementing counter
		nextSlot := &cpu.history[cpu.tail]
		*nextSlot = *slot
	}
	return
}

func (p *Plugin) Start() {
	p.cpus = p.newCpus(p.numCPU())
	p.collector.open(p.numCPU())
}

func (p *Plugin) Stop() {
	p.collector.close()
	p.cpus = nil
}

func init() {
	impl.collector = newPdhCollector(&impl)
	plugin.RegisterMetrics(&impl, "CpuCollector",
		"system.cpu.discovery", "List of detected CPUs/CPU cores, used for low-level discovery.",
		"system.cpu.load", "CPU load.",
		"system.cpu.num", "Number of CPUs.",
		"system.cpu.util", "CPU utilisation percentage.")
}
