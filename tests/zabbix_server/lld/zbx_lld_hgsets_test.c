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

#include "zbxmocktest.h"
#include "zbxmockassert.h"
#include "zbxmockutil.h"
#include "zbxcommon.h"

#include "../../../src/zabbix_server/lld/lld_host.c"

static void	host_free(zbx_lld_host_t *host)
{
	zbx_vector_uint64_destroy(&host->groupids);
	zbx_vector_uint64_destroy(&host->old_groupids);

	zbx_free(host);
}

static int	hgset_opt_to_num(const char *opt_str)
{
	if (0 == strcmp("ZBX_LLD_HGSET_OPT_DELETE", opt_str))
		return ZBX_LLD_HGSET_OPT_DELETE;
	else if (0 == strcmp("ZBX_LLD_HGSET_OPT_INSERT", opt_str))
		return ZBX_LLD_HGSET_OPT_INSERT;
	else if (0 == strcmp("ZBX_LLD_HGSET_OPT_REUSE", opt_str))
		return ZBX_LLD_HGSET_OPT_REUSE;

	return FAIL;
}
static zbx_mock_error_t	get_vector_elements_uint64(zbx_mock_handle_t object_member, zbx_vector_uint64_t *elements)
{
	zbx_mock_handle_t	element;
	zbx_mock_error_t	error;

	while (ZBX_MOCK_SUCCESS == (error = zbx_mock_vector_element(object_member, &element)))
	{
		zbx_uint64_t	val;

		if (ZBX_MOCK_SUCCESS != (error = zbx_mock_uint64(element, &val)))
			break;

		zbx_vector_uint64_append(elements, val);
	}

	zbx_vector_uint64_sort(elements, ZBX_DEFAULT_UINT64_COMPARE_FUNC);

	return error;
}

static void	get_object_member_vector_elements_uint64(zbx_mock_handle_t handle, const char *name,
		zbx_vector_uint64_t *elements)
{
	zbx_mock_error_t	error;

	if (ZBX_MOCK_END_OF_VECTOR != (error =
			get_vector_elements_uint64(zbx_mock_get_object_member_handle(handle, name), elements)))
	{
		fail_msg("Cannot read object member vector elements \"%s\": %s", name, zbx_mock_error_string(error));
	}
}

void	zbx_mock_test_entry(void **state)
{
	int				i, param_num = 0;
	zbx_mock_error_t 		error;
	zbx_mock_handle_t		vector, element;
	zbx_vector_ptr_t		hosts;
	zbx_vector_lld_hgset_ptr_t	hgsets;
	zbx_vector_uint64_t		del_hgsetids_act, del_hgsetids_exp;
	zbx_lld_host_t			*host;

	ZBX_UNUSED(state);

	zbx_mockdb_init();

	zbx_vector_uint64_create(&del_hgsetids_act);
	zbx_vector_uint64_create(&del_hgsetids_exp);
	zbx_vector_lld_hgset_ptr_create(&hgsets);
	zbx_vector_ptr_create(&hosts);

	if (ZBX_MOCK_SUCCESS != (error = zbx_mock_in_parameter("hosts", &vector)))
		fail_msg("Cannot get description of in.hosts from test case data: %s", zbx_mock_error_string(error));

	while (ZBX_MOCK_SUCCESS == (error = zbx_mock_vector_element(vector, &element)))
	{
		host = (zbx_lld_host_t *)zbx_malloc(NULL, sizeof(zbx_lld_host_t));

		host->flags = ZBX_FLAG_LLD_HOST_DISCOVERED;
		zbx_vector_uint64_create(&host->groupids);
		zbx_vector_uint64_create(&host->old_groupids);

		host->hostid = zbx_mock_get_object_member_uint64(element, "hostid");
		host->hgsetid_orig = zbx_mock_get_object_member_uint64(element, "hgsetid_orig");
		get_object_member_vector_elements_uint64(element, "res_groupids", &host->groupids);
		get_object_member_vector_elements_uint64(element, "old_groupids", &host->old_groupids);

		zbx_vector_ptr_append(&hosts, host);
	}

	if (ZBX_MOCK_END_OF_VECTOR != error)
		fail_msg("Cannot read \"hosts\" for input: %s", zbx_mock_error_string(error));

	zbx_vector_ptr_sort(&hosts, ZBX_DEFAULT_UINT64_PTR_COMPARE_FUNC);

	lld_hgsets_make(&hosts, &hgsets, &del_hgsetids_act);

	if (ZBX_MOCK_SUCCESS != (error = zbx_mock_out_parameter("hgsets", &vector)))
		fail_msg("Cannot get description of out.hgsets from test case data: %s", zbx_mock_error_string(error));

	while (ZBX_MOCK_SUCCESS == (error = zbx_mock_vector_element(vector, &element)))
	{
		const char		*tmp = NULL;
		zbx_vector_uint64_t	hgroupids;
		zbx_lld_hgset_t		*hgset;

		/* hash */

		tmp = zbx_mock_get_object_member_string(element, "hash_str");

		if (FAIL == (i = zbx_vector_lld_hgset_ptr_search(&hgsets, (void*)tmp, lld_hgset_hash_search)))
			fail_msg("Missing expected hash in actual results: %s", tmp);

		hgset = hgsets.values[i];

		/* hgsetid */
		zbx_mock_assert_uint64_eq("hgset hgsetid", zbx_mock_get_object_member_uint64(element, "hgsetid"),
				hgset->hgsetid);

		/* opt */
		zbx_mock_assert_int_eq("hgset opt", hgset_opt_to_num(zbx_mock_get_object_member_string(element, "opt")),
				hgset->opt);

		/* hgroupids */
		zbx_vector_uint64_create(&hgroupids);
		get_object_member_vector_elements_uint64(element, "hgroupids", &hgroupids);
		zbx_mock_assert_vector_uint64_eq("hgset hgroupids", &hgroupids, &hgset->hgroupids);
		zbx_vector_uint64_destroy(&hgroupids);

		param_num++;
	}

	if (ZBX_MOCK_END_OF_VECTOR != error)
		fail_msg("Cannot read expected \"hgsets\": %s", zbx_mock_error_string(error));

	zbx_mock_assert_int_eq("count of hgsets", param_num, hgsets.values_num);

	if (ZBX_MOCK_SUCCESS != (error = zbx_mock_out_parameter("hosts", &vector)))
		fail_msg("Cannot get description of out.hosts from test case data: %s", zbx_mock_error_string(error));

	param_num = 0;

	while (ZBX_MOCK_SUCCESS == (error = zbx_mock_vector_element(vector, &element)))
	{
		zbx_uint64_t	hostid;

		/* hostid */

		hostid = zbx_mock_get_object_member_int(element, "hostid");

		if (FAIL == (i = zbx_vector_ptr_bsearch(&hosts, &hostid, ZBX_DEFAULT_UINT64_PTR_COMPARE_FUNC)))
			fail_msg("Missing expected hostid in actual results: " ZBX_FS_UI64, hostid);

		host = hosts.values[i];

		/* hgsetid */
		zbx_mock_assert_uint64_eq("host hgsetid", zbx_mock_get_object_member_uint64(element, "hgsetid"),
				host->hgset->hgsetid);

		/* hash_str (checked only if hgsetid==0) */
		if (0 == host->hgset->hgsetid)
		{
			zbx_mock_assert_str_eq("host hash_str",
					zbx_mock_get_object_member_string(element, "hash_str"), host->hgset->hash_str);
		}

		/* flag */
		if (0 == (host->flags & ZBX_FLAG_LLD_HOST_UPDATE_HGSETID))
		{
			zbx_mock_assert_str_ne("host flags",
					zbx_mock_get_object_member_string(element, "update_hgsetid"), "yes");
		}
		else
		{
			zbx_mock_assert_str_eq("host flags",
					zbx_mock_get_object_member_string(element, "update_hgsetid"), "yes");
		}

		param_num++;
	}

	if (ZBX_MOCK_END_OF_VECTOR != error)
		fail_msg("Cannot read expected \"hosts\": %s", zbx_mock_error_string(error));

	zbx_mock_assert_int_eq("count of hosts", param_num, hosts.values_num);

	if (ZBX_MOCK_SUCCESS != (error = zbx_mock_out_parameter("del_hgsetids", &vector)))
	{
		fail_msg("Cannot get description of out.del_hgsetids from test case data: %s",
				zbx_mock_error_string(error));
	}

	if (ZBX_MOCK_END_OF_VECTOR != (error = get_vector_elements_uint64(vector, &del_hgsetids_exp)))
		fail_msg("Cannot read expected \"del_hgsetids\": %s", zbx_mock_error_string(error));

	zbx_mock_assert_vector_uint64_eq("del_hgsetids", &del_hgsetids_exp, &del_hgsetids_act);

	zbx_vector_ptr_clear_ext(&hosts, (zbx_clean_func_t)host_free);
	zbx_vector_ptr_destroy(&hosts);

	zbx_vector_lld_hgset_ptr_clear_ext(&hgsets, lld_hgset_free);
	zbx_vector_lld_hgset_ptr_destroy(&hgsets);

	zbx_vector_uint64_destroy(&del_hgsetids_exp);
	zbx_vector_uint64_destroy(&del_hgsetids_act);

	zbx_mockdb_destroy();
}
