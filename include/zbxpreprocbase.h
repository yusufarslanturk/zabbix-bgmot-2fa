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

#ifndef ZABBIX_PP_PREPROCBASE_H
#define ZABBIX_PP_PREPROCBASE_H

#include "zbxalgo.h"
#include "zbxvariant.h"
#include "zbxtime.h"

/* one preprocessing step history */
typedef struct
{
	int		index;
	zbx_variant_t	value;
	zbx_timespec_t	ts;
}
zbx_pp_step_history_t;

ZBX_VECTOR_DECL(pp_step_history, zbx_pp_step_history_t)

/* item preprocessing history for preprocessing steps using previous values */
typedef struct
{
	zbx_vector_pp_step_history_t	step_history;
}
zbx_pp_history_t;

zbx_pp_history_t	*zbx_pp_history_create(int history_num);
void	zbx_pp_history_init(zbx_pp_history_t *history);
void	zbx_pp_history_clear(zbx_pp_history_t *history);
void	zbx_pp_history_free(zbx_pp_history_t *history);
void	zbx_pp_history_reserve(zbx_pp_history_t *history, int history_num);
void	zbx_pp_history_add(zbx_pp_history_t *history, int index, zbx_variant_t *value, zbx_timespec_t ts);
void	zbx_pp_history_pop(zbx_pp_history_t *history, int index, zbx_variant_t *value, zbx_timespec_t *ts);

typedef enum
{
	ZBX_PP_PROCESS_PARALLEL,
	ZBX_PP_PROCESS_SERIAL
}
zbx_pp_process_mode_t;

typedef struct
{
	int	type;
	int	error_handler;
	char	*params;
	char	*error_handler_params;
}
zbx_pp_step_t;

void	zbx_pp_step_free(zbx_pp_step_t *step);

ZBX_PTR_VECTOR_DECL(pp_step_ptr, zbx_pp_step_t *)

typedef struct
{
	zbx_uint32_t		refcount;

	zbx_uint64_t		hostid;
	int			steps_num;
	zbx_pp_step_t		*steps;

	int			dep_itemids_num;
	zbx_uint64_t		*dep_itemids;

	unsigned char		type;
	unsigned char		value_type;
	unsigned char		flags;
	zbx_pp_process_mode_t	mode;

	zbx_pp_history_t	*history;	/* the preprocessing history */
	int			history_num;	/* the number of preprocessing steps requiring history */
}
zbx_pp_item_preproc_t;

zbx_pp_item_preproc_t	*zbx_pp_item_preproc_copy(zbx_pp_item_preproc_t *preproc);
zbx_pp_item_preproc_t	*zbx_pp_item_preproc_create(zbx_uint64_t hostid, unsigned char type, unsigned char value_type,
		unsigned char flags);
void	zbx_pp_item_preproc_release(zbx_pp_item_preproc_t *preproc);
int	zbx_pp_preproc_has_history(int type);

typedef struct
{
	zbx_uint64_t		itemid;
	zbx_uint64_t		revision;

	zbx_pp_item_preproc_t	*preproc;
}
zbx_pp_item_t;

void	zbx_pp_item_clear(zbx_pp_item_t *item);

#define ZBX_PP_VALUE_OPT_NONE		0x0000
#define ZBX_PP_VALUE_OPT_META		0x0001
#define ZBX_PP_VALUE_OPT_LOG		0x0002

typedef struct
{
	zbx_uint32_t	flags;
	int		mtime;
	int		timestamp;
	int		severity;
	int		logeventid;
	zbx_uint64_t	lastlogsize;
	char		*source;
}
zbx_pp_value_opt_t;

void	zbx_pp_value_opt_clear(zbx_pp_value_opt_t *opt);

#endif
