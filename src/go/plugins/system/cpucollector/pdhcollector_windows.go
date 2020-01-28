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
	"fmt"

	"zabbix.com/pkg/pdh"
	"zabbix.com/pkg/plugin"
	"zabbix.com/pkg/win32"
)

type pdhCollector struct {
	log      plugin.Logger
	hQuery   win32.PDH_HQUERY
	hCpuUtil []win32.PDH_HCOUNTER
	hCpuLoad win32.PDH_HCOUNTER
}

// open function initializes PDH query/counters for cpu metric gathering
func (c *pdhCollector) open(numCpu int) {
	var err error
	if c.hQuery, err = win32.PdhOpenQuery(nil, 0); err != nil {
		c.log.Errf("cannot open performance monitor query for CPU statistics: %s", err)
		return
	}
	// add CPU load counter
	path := pdh.CounterPath(pdh.ObjectSystem, pdh.CounterProcessorQueue)
	if c.hCpuLoad, err = win32.PdhAddCounter(c.hQuery, path, 0); err != nil {
		c.log.Errf("cannot add performance counter for CPU load statistics: %s", err)
	}

	c.hCpuUtil = make([]win32.PDH_HCOUNTER, numCpu+1)
	cpe := pdh.CounterPathElements{
		ObjectName:    pdh.CounterName(pdh.ObjectProcessor),
		InstanceName:  "_Total",
		InstanceIndex: -1,
		CounterName:   pdh.CounterName(pdh.CounterProcessorTime),
	}
	// add total cpu utilization counter
	path, err = pdh.MakePath(&cpe)
	if err != nil {
		c.log.Errf("cannot make counter path for total CPU utilization: %s", err)
	}
	c.hCpuUtil[0], err = win32.PdhAddCounter(c.hQuery, path, 0)
	if err != nil {
		c.log.Errf("cannot add performance counter for total CPU utilization: %s", err)
	}
	// add per cpu utilization counters
	if numCpu <= 64 {
		for i := 1; i <= numCpu; i++ {
			cpe.InstanceName = fmt.Sprintf("%d", i-1)
			path, err = pdh.MakePath(&cpe)
			if err != nil {
				c.log.Errf("cannot make counter path for CPU#%s utilization: %s", cpe.InstanceName, err)
			}
			c.hCpuUtil[i], err = win32.PdhAddCounter(c.hQuery, path, 0)
			if err != nil {
				c.log.Errf("cannot add performance counter for CPU#%s utilization: %s", cpe.InstanceName, err)
			}
		}
	} else {
		// TODO: handle NUMA nodes
	}
}

// close function closes opened PDH query
func (c *pdhCollector) close() {
	_ = win32.PdhCloseQuery(c.hQuery)
	c.hQuery = 0
	c.hCpuLoad = 0
	c.hCpuUtil = nil
}

func (c *pdhCollector) collect() (ok bool, err error) {
	if c.hQuery == 0 {
		return
	}
	if err = win32.PdhCollectQueryData(c.hQuery); err != nil {
		return
	}
	return true, nil
}

// cpuLoad function returns collected CPU load counter- \Processor\Processor Queue Length
func (c *pdhCollector) cpuLoad() (value float64) {
	if c.hCpuLoad == 0 {
		return
	}
	pvalue, err := win32.PdhGetFormattedCounterValueDouble(c.hCpuLoad)
	if err != nil {
		c.log.Debugf("cannot obtain CPU load counter value: %s", err)
	}
	if pvalue != nil {
		return *pvalue
	}
	return
}

// cpuLoad function returns collected CPU utilization for the specified CPU index (0 - total, 1 - first(0), ...) -
// \Processor\% Processor Time
func (c *pdhCollector) cpuUtil(cpuIndex int) (value float64) {
	if c.hCpuUtil[cpuIndex] == 0 {
		return
	}
	pvalue, err := win32.PdhGetFormattedCounterValueDouble(c.hCpuUtil[cpuIndex])
	if err != nil {
		var suffix string
		if cpuIndex != 0 {
			suffix = fmt.Sprintf("#%d", cpuIndex-1)
		}
		c.log.Debugf("cannot obtain CPU%s utilization counter value: %s", suffix, err)
	}
	if pvalue != nil {
		return *pvalue
	}
	return
}

func newPdhCollector(log plugin.Logger) (c *pdhCollector) {
	return &pdhCollector{log: log}
}
