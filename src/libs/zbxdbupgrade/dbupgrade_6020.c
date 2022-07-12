/*
** Zabbix
** Copyright (C) 2001-2022 Zabbix SIA
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

#include "dbupgrade.h"

extern unsigned char	program_type;

/*
 * 6.2 maintenance database patches
 */

#ifndef HAVE_SQLITE3

static int	DBpatch_6020000(void)
{
	return SUCCEED;
}

static int	DBpatch_6020001(void)
{
	const ZBX_FIELD	old_field = {"name", "", NULL, NULL, 64, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0};
	const ZBX_FIELD	field = {"name", "", NULL, NULL, 255, ZBX_TYPE_CHAR, ZBX_NOTNULL, 0};

	return DBmodify_field_type("group_discovery", &field, &old_field);
}

#endif

DBPATCH_START(6020)

/* version, duplicates flag, mandatory flag */

DBPATCH_ADD(6020000, 0, 1)
DBPATCH_ADD(6020001, 0, 0)

DBPATCH_END()
