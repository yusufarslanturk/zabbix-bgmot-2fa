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

#include "common.h"
#include "db.h"
#include "dbupgrade.h"

extern unsigned char	program_type;

/*
 * 4.2 maintenance database patches
 */

#ifndef HAVE_SQLITE3

static int	DBpatch_4020000(void)
{
	return SUCCEED;
}

static int	DBpatch_4020001(void)
{
	int		i;
	const char      *values[] = {
			"alarm_ok",
			"no_sound",
			"alarm_information",
			"alarm_warning",
			"alarm_average",
			"alarm_high",
			"alarm_disaster"
		};

	if (0 == (program_type & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

	for (i = 0; i < ARRSIZE(values); i++)
	{
		if (ZBX_DB_OK > DBexecute(
				"update profiles"
				" set value_str='%s.mp3'"
				" where value_str='%s.wav'"
					" and idx='web.messages'", values[i], values[i]))
		{
			return FAIL;
		}
	}

	return SUCCEED;
}
#endif

DBPATCH_START(4020)

/* version, duplicates flag, mandatory flag */

DBPATCH_ADD(4020000, 0, 1)
DBPATCH_ADD(4020001, 0, 0)

DBPATCH_END()
