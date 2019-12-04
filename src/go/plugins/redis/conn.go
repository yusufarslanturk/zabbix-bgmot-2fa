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

package redis

import (
	"crypto/sha512"
	"github.com/mediocregopher/radix/v3"
	"sync"
	"time"
	"zabbix.com/pkg/log"
)

const poolSize = 1

const clientName = "zbx_monitor"

type redisConn interface {
	Do(a radix.Action) error
}

type connId [sha512.Size]byte

type pool struct {
	conn           *radix.Pool
	uri            URI
	lastTimeAccess time.Time
}

// UpdateAccessTime updates the last time pool was accessed.
func (p *pool) UpdateAccessTime() {
	p.lastTimeAccess = time.Now()
}

// Thread-safe structure for manage connections.
type connPools struct {
	g         sync.Mutex
	m         sync.Mutex
	pools     map[connId]*pool
	keepAlive time.Duration
	timeout   time.Duration
}

// NewConnPools initializes connPools structure and runs Go Routine that watches for unused connections.
func NewConnPools(keepAlive, timeout time.Duration) *connPools {
	pools := &connPools{
		pools:     make(map[connId]*pool),
		keepAlive: keepAlive,
		timeout:   timeout,
	}

	// Repeatedly check for unused connections and close them.
	go func() {
		for range time.Tick(time.Second) {
			if err := pools.closeUnused(); err != nil {
				log.Errf("[%s] Error occurred while closing pool: %s", pluginName, err.Error())
			}
		}
	}()

	return pools
}

// create creates a new connection with given URI and password.
func (c *connPools) create(uri URI) (conn *radix.Pool, err error) {
	c.m.Lock()
	defer c.m.Unlock()

	cid := createConnectionId(uri)

	if _, ok := c.pools[cid]; ok {
		// Should never happen.
		panic("pool already exists")
	}

	// AuthConnFunc is used as radix.ConnFunc to perform AUTH and set timeout
	AuthConnFunc := func(scheme, addr string) (radix.Conn, error) {
		return radix.Dial(scheme, addr,
			radix.DialTimeout(c.timeout),
			radix.DialAuthPass(uri.Password()),
		)
	}

	conn, err = radix.NewPool(uri.Scheme(), uri.Addr(), poolSize, radix.PoolConnFunc(AuthConnFunc))
	if err != nil {
		return nil, err
	}

	err = conn.Do(radix.Cmd(nil, "CLIENT", "SETNAME", clientName))
	if err != nil {
		return nil, err
	}

	c.pools[cid] = &pool{
		conn:           conn,
		uri:            uri,
		lastTimeAccess: time.Now(),
	}

	log.Debugf("[%s] Created new connection: %s", pluginName, uri.Addr())

	return conn, nil
}

// get returns a connection with given cid if it exists and also updates lastTimeAccess, otherwise returns nil.
func (c *connPools) get(cid connId) *radix.Pool {
	c.m.Lock()
	defer c.m.Unlock()

	if pool, ok := c.pools[cid]; ok {
		pool.UpdateAccessTime()
		return pool.conn
	}

	return nil
}

// CloseUnused closes each connection that has not been accessed at least within the keepalive interval.
func (c *connPools) closeUnused() (err error) {
	var uri URI

	c.m.Lock()
	defer c.m.Unlock()

	for cid, pool := range c.pools {
		if time.Since(pool.lastTimeAccess) > time.Duration(c.keepAlive)*time.Second {
			if err = pool.conn.Close(); err == nil {
				uri = pool.uri
				delete(c.pools, cid)
				log.Debugf("[%s] Closed unused connection: %s", pluginName, uri.Addr())
			}
		}
	}

	// Return the last error only.
	return
}

// GetConnection returns an existing connection or creates a new one.
func (c *connPools) GetConnection(uri URI) (conn *radix.Pool, err error) {
	c.g.Lock()
	defer c.g.Unlock()

	cid := createConnectionId(uri)
	conn = c.get(cid)

	if conn == nil {
		conn, err = c.create(uri)
	}

	return
}

// createConnectionId returns sha512 hash from URI.
func createConnectionId(uri URI) connId {
	// TODO: add memoization
	return connId(sha512.Sum512([]byte((uri.Uri()))))
}
