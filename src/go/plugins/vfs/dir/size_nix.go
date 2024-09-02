//go:build !windows
// +build !windows

/*
** Zabbix
** Copyright (C) 2001-2024 Zabbix SIA
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

package dir

import (
	"fmt"
	"io/fs"
	"syscall"
)

func (sp *sizeParams) getSize(fs fs.FileInfo, path string) (int64, error) {
	sys, ok := fs.Sys().(*syscall.Stat_t)
	if !ok {
		return 0, fmt.Errorf("failed to read %s file size", fs.Name())
	}

	if !sp.diskMode {
		return sys.Size, nil
	}

	return sys.Blocks * diskBlockSize, nil
}

func skipDir(d fs.DirEntry) bool {
	return false
}

func (sp *sizeParams) handleHomeDir(path string, d fs.DirEntry) (int64, error) {
	parentSize, err := sp.getParentSize(d)
	if err != nil {
		return 0, err
	}

	return parentSize, nil
}

func (cp *common) osSkip(path string, d fs.DirEntry) bool {
	i, err := d.Info()
	if err != nil {
		impl.Logger.Errf("failed to get file info for path %s, %s", path, err.Error())

		return true
	}

	iStat, ok := i.Sys().(*syscall.Stat_t)
	if !ok {
		impl.Logger.Errf("failed to get file info for path %s", path)

		return true
	}

	if !i.Mode().IsRegular() || iStat.Nlink <= 1 {
		return false
	}

	iData := inodeData{
		Dev: uint64(iStat.Dev),
		Ino: uint64(iStat.Ino),
	}

	_, ok = cp.files[iData]
	if ok {
		return true
	}

	cp.files[iData] = true

	return false
}
