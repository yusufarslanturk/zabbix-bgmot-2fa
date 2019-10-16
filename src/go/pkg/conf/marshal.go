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

// Package conf provides .conf file loading and unmarshaling
package conf

import (
	"errors"
	"reflect"
	"strconv"
)

func getValue(node *Node, value reflect.Value) (err error) {
	switch value.Type().Kind() {
	case reflect.Int, reflect.Int8, reflect.Int16, reflect.Int32, reflect.Int64:
		node.values = append(node.values, []byte(strconv.FormatInt(value.Int(), 10)))
	case reflect.Uint, reflect.Uint8, reflect.Uint16, reflect.Uint32, reflect.Uint64:
		node.values = append(node.values, []byte(strconv.FormatUint(value.Uint(), 10)))
	case reflect.Float32, reflect.Float64:
		node.values = append(node.values, []byte(strconv.FormatFloat(value.Float(), 'g', -1, 64)))
	case reflect.Bool:
		node.values = append(node.values, []byte(strconv.FormatBool(value.Bool())))
	case reflect.String:
		node.values = append(node.values, []byte(value.String()))
	case reflect.Struct:
		rt := value.Type()
		for i := 0; i < rt.NumField(); i++ {
			getValue(node.getOrAdd(rt.Field(i).Name), value.Field(i))
		}
	case reflect.Ptr:
		getValue(node, value.Elem())
	case reflect.Slice:
		for i := 0; i < value.Len(); i++ {
			getValue(node, value.Index(i))
		}
	case reflect.Map:
		for _, key := range value.MapKeys() {
			getValue(node.getOrAdd(key.String()), value.MapIndex(key))
		}
	}
	return
}

func Marshal(v interface{}) (node interface{}, err error) {
	rv := reflect.ValueOf(v)
	if rv.Kind() != reflect.Ptr || rv.IsNil() {
		return nil, errors.New("Invalid input parameter")
	}

	root := &Node{
		name:   "",
		used:   false,
		values: make([][]byte, 0),
		nodes:  make([]*Node, 0),
		parent: nil,
		line:   0}

	if err = getValue(root, rv.Elem()); err != nil {
		return
	}

	return root, nil
}
