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

#include "zbxmocktest.h"
#include "zbxmockdata.h"
#include "zbxmockassert.h"
#include "zbxmockutil.h"

#include "common.h"
#include "zbxjson.h"
#include "../../../src/libs/zbxjson/jsonpath.h"

void	zbx_mock_test_entry(void **state)
{
	const char		*path, *value, *expected_result;
	char			*actual_result, *macro_value;
	size_t			alloc_len = 0, offset = 0;
	int			l, r;

	ZBX_UNUSED(state);

	path = zbx_mock_get_parameter_string("in.path");
	value = zbx_mock_get_parameter_string("in.value");
	expected_result = zbx_mock_get_parameter_string("out.result");

	macro_value = strstr(path, "{#");

	if (NULL == macro_value)
		fail_msg("No macro in source path");

	l = macro_value - path;
	r = strstr(macro_value, "}") - path;

	macro_value = NULL;
	zbx_strcpy_alloc(&macro_value, &alloc_len, &offset, value);
	alloc_len = offset = 0;
	zbx_strcpy_alloc(&actual_result, &alloc_len, &offset, path);

	zbx_jsonpath_substitute_macro(actual_result, l, &macro_value);

	offset = alloc_len = strlen(path) + 1;
	zbx_replace_mem_dyn(&actual_result, &offset, &alloc_len, l, r - l + 1, macro_value, strlen(macro_value));

	if (0 != strcmp(expected_result, actual_result))
	{
		fail_msg("Actual: \"%s\" != expected: \"%s\"", actual_result, expected_result);
	}

	zbx_free(macro_value);
	zbx_free(actual_result);
}
