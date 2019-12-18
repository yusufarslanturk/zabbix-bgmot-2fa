// +build windows

package win32

import (
	"fmt"
	"math"
	"syscall"
	"testing"
	"unsafe"
)

func TestGetIfTable(t *testing.T) {
	//	var table MIB_IFTABLE
	//	var buf []byte
	var size uint32

	ok, err := GetIfTable(nil, &size, false)

	if err != nil {
		t.Errorf("%s", err)
		return
	}

	if !ok {
		fmt.Printf("size: %d\n", size)
	}

	buf := make([]byte, size)
	table := (*MIB_IFTABLE)(unsafe.Pointer(&buf[0]))
	ok, err = GetIfTable(table, &size, false)

	if err != nil {
		t.Errorf("%s", err)
		return
	}

	if !ok {
		fmt.Printf("size: %d\n", size)
	} else {
		fmt.Printf("entries: %d\n", table.NumEntries)
		rows := (*[math.MaxInt32]MIB_IFROW)(unsafe.Pointer(&table.Table[0]))[:table.NumEntries:table.NumEntries]

		for i := range rows {
			row := &rows[i]
			fmt.Printf("<%d> %s\n\n", i, string(row.Descr[:row.DescrLen]))
		}
	}
}

func TestGetIfTable2(t *testing.T) {

	table, err := GetIfTable2()

	if err != nil {
		t.Errorf("%s", err)
		return
	}

	fmt.Printf("entries: %d\n", table.NumEntries)

	rows := (*[math.MaxInt32]MIB_IF_ROW2)(unsafe.Pointer(&table.Table[0]))[:table.NumEntries:table.NumEntries]

	for i := range rows {
		row := &rows[i]
		fmt.Printf("<%d> %s\n", i, syscall.UTF16ToString(row.Description[:]))
	}

	FreeMibTable(table)
}
