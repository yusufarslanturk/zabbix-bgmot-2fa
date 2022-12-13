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

#include "zbxstats.h"

#include "zbxalgo.h"
#include "zbxcacheconfig.h"
#include "zbxcachehistory.h"
#include "zbxcomms.h"
#include "zbxjson.h"
#include "zbxself.h"

static zbx_get_program_type_f		get_program_type_cb;
static zbx_vector_stats_ext_func_t	stats_ext_funcs;
static zbx_vector_stats_ext_func_t	stats_data_funcs;

ZBX_PTR_VECTOR_IMPL(stats_ext_func, zbx_stats_ext_func_entry_t *)

void	zbx_init_library_stats(zbx_get_program_type_f get_program_type)
{
	get_program_type_cb = get_program_type;

	zbx_vector_stats_ext_func_create(&stats_data_funcs);
	zbx_vector_stats_ext_func_create(&stats_ext_funcs);
}

/******************************************************************************
 *                                                                            *
 * Purpose: register callback to add information to main element              *
 *                                                                            *
 * Parameters: stats_ext_get_cb - [IN] statistics extension callback          *
 *                                                                            *
 ******************************************************************************/
void	zbx_register_stats_ext_func(zbx_zabbix_stats_ext_get_func_t stats_ext_get_cb)
{
	zbx_stats_ext_func_entry_t	*entry;

	entry = (zbx_stats_ext_func_entry_t *)zbx_malloc(NULL, sizeof(zbx_stats_ext_func_entry_t));
	entry->stats_ext_get_cb = stats_ext_get_cb;

	zbx_vector_stats_ext_func_append(&stats_ext_funcs, entry);
}

/******************************************************************************
 *                                                                            *
 * Purpose: register callback to add information to data subelement           *
 *                                                                            *
 * Parameters: stats_ext_get_cb - [IN] statistics extension callback          *
 *                                                                            *
 ******************************************************************************/
void	zbx_register_stats_data_func(zbx_zabbix_stats_ext_get_func_t stats_ext_get_cb)
{
	zbx_stats_ext_func_entry_t	*entry;

	entry = (zbx_stats_ext_func_entry_t *)zbx_malloc(NULL, sizeof(zbx_stats_ext_func_entry_t));
	entry->stats_ext_get_cb = stats_ext_get_cb;

	zbx_vector_stats_ext_func_append(&stats_data_funcs, entry);
}

/******************************************************************************
 *                                                                            *
 * Purpose: collects all metrics required for Zabbix stats request            *
 *                                                                            *
 * Parameters: json             - [OUT] the json data                         *
 *             config_comms     - [IN] Zabbix server/proxy comms config       *
 *                                                                            *
 ******************************************************************************/
void	zbx_zabbix_stats_get(struct zbx_json *json, const zbx_config_comms_args_t *config_comms,
		int config_server_startup_time)
{
	int			i;
	zbx_config_cache_info_t	count_stats;
	zbx_wcache_info_t	wcache_info;
	zbx_process_info_t	process_stats[ZBX_PROCESS_TYPE_COUNT];
	int			proc_type;

	DCget_count_stats_all(&count_stats);

	/* zabbix[boottime] */
	zbx_json_addint64(json, "boottime", config_server_startup_time);

	/* zabbix[uptime] */
	zbx_json_addint64(json, "uptime", time(NULL) - config_server_startup_time);

	/* zabbix[hosts] */
	zbx_json_adduint64(json, "hosts", count_stats.hosts);

	/* zabbix[items] */
	zbx_json_adduint64(json, "items", count_stats.items);

	/* zabbix[items_unsupported] */
	zbx_json_adduint64(json, "items_unsupported", count_stats.items_unsupported);

	/* zabbix[requiredperformance] */
	zbx_json_addfloat(json, "requiredperformance", count_stats.requiredperformance);

	for (i = 0; i < stats_data_funcs.values_num; i++)
	{
		stats_data_funcs.values[i]->stats_ext_get_cb(json, config_comms);
	}

	/* zabbix[rcache,<cache>,<mode>] */
	zbx_json_addobject(json, "rcache");
	zbx_json_adduint64(json, "total", *(zbx_uint64_t *)DCconfig_get_stats(ZBX_CONFSTATS_BUFFER_TOTAL));
	zbx_json_adduint64(json, "free", *(zbx_uint64_t *)DCconfig_get_stats(ZBX_CONFSTATS_BUFFER_FREE));
	zbx_json_addfloat(json, "pfree", *(double *)DCconfig_get_stats(ZBX_CONFSTATS_BUFFER_PFREE));
	zbx_json_adduint64(json, "used", *(zbx_uint64_t *)DCconfig_get_stats(ZBX_CONFSTATS_BUFFER_USED));
	zbx_json_addfloat(json, "pused", *(double *)DCconfig_get_stats(ZBX_CONFSTATS_BUFFER_PUSED));
	zbx_json_close(json);

	/* zabbix[version] */
	zbx_json_addstring(json, "version", ZABBIX_VERSION, ZBX_JSON_TYPE_STRING);

	/* zabbix[wcache,<cache>,<mode>] */
	DCget_stats_all(&wcache_info);
	zbx_json_addobject(json, "wcache");

	zbx_json_addobject(json, "values");
	zbx_json_adduint64(json, "all", wcache_info.stats.history_counter);
	zbx_json_adduint64(json, "float", wcache_info.stats.history_float_counter);
	zbx_json_adduint64(json, "uint", wcache_info.stats.history_uint_counter);
	zbx_json_adduint64(json, "str", wcache_info.stats.history_str_counter);
	zbx_json_adduint64(json, "log", wcache_info.stats.history_log_counter);
	zbx_json_adduint64(json, "text", wcache_info.stats.history_text_counter);
	zbx_json_adduint64(json, "not supported", wcache_info.stats.notsupported_counter);
	zbx_json_close(json);

	zbx_json_addobject(json, "history");
	zbx_json_addfloat(json, "pfree", 100 * (double)wcache_info.history_free / wcache_info.history_total);
	zbx_json_adduint64(json, "free", wcache_info.history_free);
	zbx_json_adduint64(json, "total", wcache_info.history_total);
	zbx_json_adduint64(json, "used", wcache_info.history_total - wcache_info.history_free);
	zbx_json_addfloat(json, "pused", 100 * (double)(wcache_info.history_total - wcache_info.history_free) /
			wcache_info.history_total);
	zbx_json_close(json);

	zbx_json_addobject(json, "index");
	zbx_json_addfloat(json, "pfree", 100 * (double)wcache_info.index_free / wcache_info.index_total);
	zbx_json_adduint64(json, "free", wcache_info.index_free);
	zbx_json_adduint64(json, "total", wcache_info.index_total);
	zbx_json_adduint64(json, "used", wcache_info.index_total - wcache_info.index_free);
	zbx_json_addfloat(json, "pused", 100 * (double)(wcache_info.index_total - wcache_info.index_free) /
			wcache_info.index_total);
	zbx_json_close(json);

	if (0 != (get_program_type_cb() & ZBX_PROGRAM_TYPE_SERVER))
	{
		zbx_json_addobject(json, "trend");
		zbx_json_addfloat(json, "pfree", 100 * (double)wcache_info.trend_free / wcache_info.trend_total);
		zbx_json_adduint64(json, "free", wcache_info.trend_free);
		zbx_json_adduint64(json, "total", wcache_info.trend_total);
		zbx_json_adduint64(json, "used", wcache_info.trend_total - wcache_info.trend_free);
		zbx_json_addfloat(json, "pused", 100 * (double)(wcache_info.trend_total - wcache_info.trend_free) /
				wcache_info.trend_total);
		zbx_json_close(json);
	}

	zbx_json_close(json);

	for (i = 0; i < stats_ext_funcs.values_num; i++)
	{
		stats_ext_funcs.values[i]->stats_ext_get_cb(json, config_comms);
	}

	/* zabbix[process,<type>,<mode>,<state>] */
	zbx_json_addobject(json, "process");

	if (SUCCEED == zbx_get_all_process_stats(process_stats))
	{
		for (proc_type = 0; proc_type < ZBX_PROCESS_TYPE_COUNT; proc_type++)
		{
			if (0 == process_stats[proc_type].count)
				continue;

			zbx_json_addobject(json, get_process_type_string(proc_type));
			zbx_json_addobject(json, "busy");
			zbx_json_addfloat(json, "avg", process_stats[proc_type].busy_avg);
			zbx_json_addfloat(json, "max", process_stats[proc_type].busy_max);
			zbx_json_addfloat(json, "min", process_stats[proc_type].busy_min);
			zbx_json_close(json);
			zbx_json_addobject(json, "idle");
			zbx_json_addfloat(json, "avg", process_stats[proc_type].idle_avg);
			zbx_json_addfloat(json, "max", process_stats[proc_type].idle_max);
			zbx_json_addfloat(json, "min", process_stats[proc_type].idle_min);
			zbx_json_close(json);
			zbx_json_addint64(json, "count", process_stats[proc_type].count);
			zbx_json_close(json);
		}
	}

	zbx_json_close(json);

	zbx_json_close(json);
}
