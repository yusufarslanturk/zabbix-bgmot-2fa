//go:build windows
// +build windows

/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
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

package perfmon

import (
	"errors"
	"fmt"
	"strconv"
	"sync"
	"time"

	"git.zabbix.com/ap/plugin-support/plugin"
	"git.zabbix.com/ap/plugin-support/zbxerr"
	"zabbix.com/pkg/pdh"
	"zabbix.com/pkg/win32"
)

func init() {
	plugin.RegisterMetrics(
		&impl,
		"WindowsPerfMon",
		"perf_counter", "Value of any Windows performance counter.",
		"perf_counter_en", "Value of any Windows performance counter in English.",
	)
}

const (
	maxInactivityPeriod = time.Hour * 25
	maxInterval         = 60 * 15

	langDefault = 0
	langEnglish = 1

	debugRetNum = 255
)

var impl Plugin = Plugin{
	counters:  make(map[perfCounterIndex]*perfCounter),
	indexOrig: make(map[string]*perfIndexOrig),
}

type perfCounterIndex struct {
	pathPdh string
	lang    int
}

type perfIndexOrig struct {
	indexPdh   perfCounterIndex
	interval   int64
	err        error
	lastAccess time.Time
}

type perfCounterAddInfo struct {
	indexPdh perfCounterIndex
	interval int64
	pathOrig string
}

type perfCounter struct {
	lastAccess time.Time
	interval   int
	handle     win32.PDH_HCOUNTER
	history    []*float64
	head, tail historyIndex
	err        error
}

// Plugin -
type Plugin struct {
	plugin.Base
	mutex        sync.Mutex
	historyMutex sync.Mutex
	counters     map[perfCounterIndex]*perfCounter
	indexOrig    map[string]*perfIndexOrig
	query        win32.PDH_HQUERY
	collectError error
}

type debugPoint struct {
	tDuration int64
	number    int
}

type historyIndex int

func (p *Plugin) debugExport(dp chan debugPoint) {
	var dpLocal debugPoint

	for {
		select {
		case dpLocal = <-dp:

			if dpLocal.tDuration > 2 /* ms */ {
				p.Warningf("long perfCounter exporter dpid %d: %f ms", dpLocal.number,
					float64(dpLocal.tDuration))

				return
			}
			if dpLocal.number >= debugRetNum {
				return
			}
		case <-time.After(20 * time.Millisecond):
			p.Warningf("perfCounter exporter longer that 20ms last dpid: %d", dpLocal.number)

			return
		}
	}
}

// Export -
func (p *Plugin) Export(key string, params []string, ctx plugin.ContextProvider) (any, error) {
	var lang int
	var dp debugPoint

	dpChan := make(chan debugPoint, 1)

	go p.debugExport(dpChan)

	tStart := time.Now().UnixMilli()
	dpChan <- dp
	switch key {
	case "perf_counter":
		lang = langDefault
	case "perf_counter_en":
		lang = langEnglish
	default:
		dp.number = debugRetNum + 1
		dp.tDuration = time.Now().UnixMilli() - tStart
		dpChan <- dp
		return nil, zbxerr.New(fmt.Sprintf("metric key %q not found", key)).Wrap(zbxerr.ErrorUnsupportedMetric)
	}

	if ctx == nil {
		dp.number = debugRetNum + 2
		dp.tDuration = time.Now().UnixMilli() - tStart
		return nil, zbxerr.New("this item is available only in daemon mode")
	}

	if len(params) > 2 {
		dp.number = debugRetNum + 3
		dp.tDuration = time.Now().UnixMilli() - tStart
		dpChan <- dp
		return nil, zbxerr.ErrorTooManyParameters
	}
	if len(params) == 0 || params[0] == "" {
		dp.number = debugRetNum + 4
		dp.tDuration = time.Now().UnixMilli() - tStart
		dpChan <- dp
		return nil, zbxerr.New("invalid first parameter")
	}

	var interval int64 = 1
	var err error

	if len(params) == 2 && params[1] != "" {
		if interval, err = strconv.ParseInt(params[1], 10, 32); err != nil {
			dp.number = debugRetNum + 5
			dp.tDuration = time.Now().UnixMilli() - tStart
			dpChan <- dp
			return nil, zbxerr.New("invalid second parameter").Wrap(err)
		}

		if interval < 1 || interval > maxInterval {
			dp.number = debugRetNum + 6
			dp.tDuration = time.Now().UnixMilli() - tStart
			dpChan <- dp
			return nil, zbxerr.New(fmt.Sprintf("interval %d out of range [%d, %d]", interval, 1, maxInterval))
		}
	}

	dp.number = 1
	dp.tDuration = time.Now().UnixMilli() - tStart
	dpChan <- dp

	p.historyMutex.Lock()
	defer p.historyMutex.Unlock()
	indexExt, ok := p.indexOrig[params[0]]
	if !ok {
		p.indexOrig[params[0]] = &perfIndexOrig{
			indexPdh:   perfCounterIndex{lang: lang},
			interval:   interval,
			lastAccess: time.Now(),
		}
		dp.number = debugRetNum + 7
		dp.tDuration = time.Now().UnixMilli() - tStart
		dpChan <- dp

		return nil, nil
	}

	indexExt.interval = interval
	indexExt.lastAccess = time.Now()

	if indexExt.err != nil {
		dp.number = debugRetNum + 8
		dp.tDuration = time.Now().UnixMilli() - tStart
		dpChan <- dp

		return nil, indexExt.err
	}

	index := indexExt.indexPdh
	dp.number = 2
	dp.tDuration = time.Now().UnixMilli() - tStart
	dpChan <- dp
	counter, ok := p.counters[index]
	if !ok {
		dp.number = debugRetNum + 9
		dp.tDuration = time.Now().UnixMilli() - tStart
		dpChan <- dp
		p.Debugf(`Performance counter %s not found while is present in added counters list, will be added on the`,
			` next collector step`, index.pathPdh)

		return nil, nil
	}

	if p.collectError != nil {
		dp.number = debugRetNum + 10
		dp.tDuration = time.Now().UnixMilli() - tStart
		dpChan <- dp

		return nil, p.collectError
	}

	dp.number = 3
	dp.tDuration = time.Now().UnixMilli() - tStart
	dpChan <- dp
	retValue, retErrror := counter.getHistory(int(interval))

	dp.number = debugRetNum
	dp.tDuration = time.Now().UnixMilli() - tStart
	dpChan <- dp

	return retValue, retErrror
}

func (p *Plugin) debugCollect(dp chan debugPoint) {
	var dpLocal debugPoint

	for {
		select {
		case dpLocal = <-dp:

			if dpLocal.tDuration > 2 /* ms */ {
				p.Warningf("long perfCounter collector dpid %d: %f ms", dpLocal.number,
					float64(dpLocal.tDuration))

				return
			}
			if dpLocal.number >= debugRetNum {
				return
			}
		case <-time.After(20 * time.Millisecond):
			p.Warningf("perfCounter collector longer that 20ms last dpid: %d", dpLocal.number)

			return
		}
	}
}

func (p *Plugin) Collect() error {
	var dp debugPoint
	var addCounters []perfCounterAddInfo

	dpChan := make(chan debugPoint, 1)
	go p.debugCollect(dpChan)

	tStart := time.Now().UnixMilli()
	dpChan <- dp

	expireTime := time.Now().Add(-maxInactivityPeriod)

	p.mutex.Lock()
	defer p.mutex.Unlock()

	p.historyMutex.Lock()
	for pathOrig, indexExt := range p.indexOrig {
		if indexExt.lastAccess.Before(expireTime) {
			delete(p.indexOrig, pathOrig)
			continue
		}

		if indexExt.err == nil && indexExt.indexPdh.pathPdh == "" {
			addCounters = append(addCounters, perfCounterAddInfo{
				pathOrig: pathOrig,
				indexPdh: indexExt.indexPdh,
				interval: indexExt.interval,
			})
		}
	}
	p.historyMutex.Unlock()

	dp.number = 1
	dp.tDuration = time.Now().UnixMilli() - tStart
	dpChan <- dp

	if len(p.counters) == 0 && len(addCounters) == 0 {
		dp.number = debugRetNum + 1
		dp.tDuration = time.Now().UnixMilli() - tStart
		dpChan <- dp
		return nil
	}

	var err error
	if p.query == 0 {
		p.query, err = win32.PdhOpenQuery(nil, 0)
		if err != nil {
			dp.number = debugRetNum + 2
			dp.tDuration = time.Now().UnixMilli() - tStart
			dpChan <- dp
			return zbxerr.New("cannot open query").Wrap(err)
		}
	}

	dp.number = 2
	dp.tDuration = time.Now().UnixMilli() - tStart
	dpChan <- dp

	p.historyMutex.Lock()
	p.collectError = nil
	p.historyMutex.Unlock()

	for _, c := range addCounters {
		c.indexPdh.pathPdh, err = pdh.ConvertPath(c.pathOrig)
		if err != nil {
			p.historyMutex.Lock()
			p.indexOrig[c.pathOrig].err = zbxerr.New(
				fmt.Sprintf("failed to convert performance counter path %s", c.pathOrig),
			).Wrap(err)
			p.historyMutex.Unlock()

			continue
		}

		p.historyMutex.Lock()
		p.indexOrig[c.pathOrig].indexPdh.pathPdh = c.indexPdh.pathPdh
		p.historyMutex.Unlock()

		err = p.addCounter(c.indexPdh, c.interval)
		if err != nil {
			p.historyMutex.Lock()
			p.indexOrig[c.pathOrig].err = zbxerr.New(
				fmt.Sprintf("failed to get counter for path %q and lang %d", c.indexPdh.pathPdh, c.indexPdh.lang),
			).Wrap(err)
			p.historyMutex.Unlock()
		}
	}

	dp.number = 3
	dp.tDuration = time.Now().UnixMilli() - tStart
	dpChan <- dp

	err = p.setCounterData()

	dp.number = 4
	dp.tDuration = time.Now().UnixMilli() - tStart
	dpChan <- dp

	if err != nil {
		p.Warningf("reset counter query: '%s'", err)

		p.historyMutex.Lock()
		p.collectError = err
		p.historyMutex.Unlock()

		err2 := win32.PdhCloseQuery(p.query)
		if err2 != nil {
			p.Warningf("error while closing query '%s'", err2)
		}

		p.query = 0

		dp.number = debugRetNum + 4
		dp.tDuration = time.Now().UnixMilli() - tStart
		dpChan <- dp
		return err
	}

	dp.number = debugRetNum
	dp.tDuration = time.Now().UnixMilli() - tStart
	dpChan <- dp

	return nil
}

func (p *Plugin) Period() int {
	return 1
}

func (p *Plugin) Start() {
}

func (p *Plugin) Stop() {
}

func (p *Plugin) setCounterData() error {
	errCollect := win32.PdhCollectQueryData(p.query)
	if errCollect != nil {
		errCollect = fmt.Errorf("cannot collect value %s", errCollect)
	}

	expireTime := time.Now().Add(-maxInactivityPeriod)

	for index, c := range p.counters {
		if c.lastAccess.Before(expireTime) || errCollect != nil {
			err2 := win32.PdhRemoveCounter(c.handle)
			if err2 != nil {
				p.Warningf("error while removing counter '%s': %s", index.pathPdh, err2)
			}

			p.historyMutex.Lock()
			delete(p.counters, index)
			p.historyMutex.Unlock()

			continue
		}

		c.err = nil

		histValue, err := win32.PdhGetFormattedCounterValueDouble(c.handle, 1)
		p.historyMutex.Lock()
		if err != nil {
			zbxErr := zbxerr.New(
				fmt.Sprintf("failed to retrieve pdh counter value double for index %s", index.pathPdh),
			).Wrap(err)
			if !errors.Is(err, win32.NegDenomErr) {
				c.err = zbxErr
			}

			p.Debugf("%s", zbxErr)
		} else {
			c.history[c.tail] = histValue
		}

		if c.tail = c.tail.inc(c.interval); c.tail == c.head {
			c.head = c.head.inc(c.interval)
		}
		p.historyMutex.Unlock()
	}

	return errCollect
}

// addCounter adds new performance counter to query. The plugin mutex must be locked.
func (p *Plugin) addCounter(index perfCounterIndex, interval int64) error {
	handle, err := p.getCounters(index)
	if err != nil {
		return err
	}

	// extend the interval buffer by 1 to reserve space so tail/head doesn't overlap
	// when the buffer is full
	interval++

	p.counters[index] = &perfCounter{
		lastAccess: time.Now(),
		history:    make([]*float64, interval),
		interval:   int(interval),
		handle:     handle,
	}

	return nil
}

func (p *Plugin) getCounters(index perfCounterIndex) (win32.PDH_HCOUNTER, error) {
	var counter win32.PDH_HCOUNTER
	var err error

	if index.lang == langEnglish {
		counter, err = win32.PdhAddEnglishCounter(p.query, index.pathPdh, 0)
		if err != nil {
			return 0, zbxerr.New("cannot add english counter").Wrap(err)
		}

		return counter, nil
	}

	counter, err = win32.PdhAddCounter(p.query, index.pathPdh, 0)
	if err != nil {
		return 0, zbxerr.New("cannot add counter").Wrap(err)
	}

	return counter, nil
}

func (c *perfCounter) getHistory(interval int) (value interface{}, err error) {
	c.lastAccess = time.Now()
	if c.err != nil {
		return nil, c.err
	}

	// extend history buffer if necessary
	if c.interval < interval+1 {
		h := make([]*float64, interval+1)
		copy(h, c.history)
		c.history = h
		c.interval = interval + 1
	}

	totalnum := int(c.tail - c.head)
	if totalnum < 0 {
		totalnum += c.interval
	}
	if totalnum == 0 {
		// not enough samples collected
		return
	}
	if interval == 1 {
		if pvalue := c.history[c.tail.dec(c.interval)]; pvalue != nil {
			return *pvalue, nil
		}
		return nil, nil
	}

	if totalnum < interval {
		interval = totalnum
	}
	start := c.tail.sub(interval, c.interval)
	var total, num float64
	for index := start; index != c.tail; index = index.inc(c.interval) {
		if pvalue := c.history[index]; pvalue != nil {
			total += *c.history[index]
			num++
		}
	}
	if num != 0 {
		return total / num, nil
	}

	return nil, nil
}

func (h historyIndex) inc(interval int) historyIndex {
	h++
	if int(h) == interval {
		h = 0
	}

	return h
}

func (h historyIndex) dec(interval int) historyIndex {
	h--
	if int(h) < 0 {
		h = historyIndex(interval - 1)
	}

	return h
}

func (h historyIndex) sub(value int, interval int) historyIndex {
	h -= historyIndex(value)
	for int(h) < 0 {
		h += historyIndex(interval)
	}

	return h
}
