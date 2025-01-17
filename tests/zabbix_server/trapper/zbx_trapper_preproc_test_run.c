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

#include "zbxmocktest.h"
#include "zbxmockassert.h"
#include "zbxmockutil.h"
#include "zbxmockjson.h"
#include "zbxembed.h"
#include "libs/zbxpreproc/pp_execute.h"
#include "zabbix_server/trapper/trapper_preproc.h"
#include "zbx_item_constants.h"

zbx_es_t	es_engine;

int	__wrap_zbx_preprocessor_test(unsigned char value_type, const char *value, const zbx_timespec_t *ts,
		unsigned char state, const zbx_vector_pp_step_ptr_t *steps, zbx_vector_pp_result_ptr_t *results,
		zbx_pp_history_t *history, char **error);

int	__wrap_zbx_db_get_user_by_active_session(const char *sessionid, zbx_user_t *user);
int	__wrap_zbx_db_get_user_by_auth_token(const char *formatted_auth_token_hash, zbx_user_t *user);
void	__wrap_zbx_user_init(zbx_user_t *user);
void	__wrap_zbx_user_free(zbx_user_t *user);
void	__wrap_zbx_init_agent_result(AGENT_RESULT *result);
void	__wrap_zbx_free_agent_result(AGENT_RESULT *result);

int	__wrap_zbx_preprocessor_test(unsigned char value_type, const char *value, const zbx_timespec_t *ts,
		unsigned char state, const zbx_vector_pp_step_ptr_t *steps, zbx_vector_pp_result_ptr_t *results,
		zbx_pp_history_t *history, char **error)
{
	int			i, results_num;
	zbx_pp_result_t		*results_out = NULL, *result;
	zbx_variant_t		value_in, value_out;
	zbx_pp_context_t	ctx;
	zbx_pp_item_preproc_t	*preproc;

	ZBX_UNUSED(error);

	pp_context_init(&ctx);

	if (ITEM_STATE_NORMAL == state)
		zbx_variant_set_str(&value_in, zbx_strdup(NULL, value));
	else
		zbx_variant_set_error(&value_in, zbx_strdup(NULL, value));

	preproc = zbx_pp_item_preproc_create(ITEM_TYPE_TRAPPER, value_type, 0);

	preproc->steps = zbx_malloc(NULL, (size_t)steps->values_num * sizeof(zbx_pp_step_t));
	for (i = 0; i < steps->values_num; i++)
		preproc->steps[i] = *steps->values[i];
	preproc->steps_num = steps->values_num;

	/* prepare history */
	if (NULL != history)
	{
		preproc->history = zbx_pp_history_create(0);
		*preproc->history = *history;
		preproc->history_num = 1;
		memset(history, 0, sizeof(zbx_pp_history_t));
	}

	zbx_variant_set_none(&value_out);

	pp_execute(&ctx, preproc, NULL, &value_in, *ts, &value_out, &results_out, &results_num);

	/* copy results */
	for (i = 0; i < results_num; i++)
	{
		result = (zbx_pp_result_t *)zbx_malloc(NULL, sizeof(zbx_pp_result_t));
		*result = results_out[i];
		zbx_vector_pp_result_ptr_append(results, result);
	}

	/* copy history */
	{
		zbx_pp_history_clear(history);
		zbx_pp_history_init(history);

		if (NULL != preproc->history)
		{
			*history = *preproc->history;
			zbx_free(preproc->history);
		}
	}

	if (ZBX_VARIANT_ERR != value_out.type)
	{
		zbx_variant_clear(&value_out);
	}
	else
	{
		*error = value_out.data.err;
		zbx_variant_set_none(&value_out);
	}

	zbx_variant_clear(&value_in);

	preproc->steps_num = 0;
	zbx_free(preproc->steps);
	zbx_pp_item_preproc_release(preproc);

	zbx_free(results_out);
	pp_context_destroy(&ctx);

	return SUCCEED;
}

int	__wrap_zbx_db_get_user_by_active_session(const char *sessionid, zbx_user_t *user)
{
	ZBX_UNUSED(sessionid);

	user->type = USER_TYPE_ZABBIX_ADMIN;
	user->userid = 0;

	return SUCCEED;
}

int	__wrap_zbx_db_get_user_by_auth_token(const char *formatted_auth_token_hash, zbx_user_t *user)
{
	ZBX_UNUSED(formatted_auth_token_hash);

	user->type = USER_TYPE_ZABBIX_ADMIN;
	user->userid = 0;

	return SUCCEED;
}

void	__wrap_zbx_user_init(zbx_user_t *user)
{
	user->username = NULL;
}

void	__wrap_zbx_user_free(zbx_user_t *user)
{
	zbx_free(user->username);
}

void	__wrap_zbx_init_agent_result(AGENT_RESULT *result)
{
	ZBX_UNUSED(result);
}

void	__wrap_zbx_free_agent_result(AGENT_RESULT *result)
{
	ZBX_UNUSED(result);
}


void	zbx_mock_test_entry(void **state)
{
	const char		*request, *response = NULL, *value_append = NULL, *expected_truncation = NULL;
	char			*error = NULL, *value_override = NULL,
				*request_override = NULL, *response_override = NULL;
	struct zbx_json_parse	jp;
	struct zbx_json		out;
	int			returned_ret, expected_ret;
	zbx_mock_handle_t	handle;
	zbx_uint64_t		value_gen_length = 0, expected_data_len = 0;
	size_t			tmp_alloc = 0, tmp_offset = 0;

	ZBX_UNUSED(state);

	zbx_json_init(&out, 1024);
	expected_ret = zbx_mock_str_to_return_code(zbx_mock_get_parameter_string("out.return"));

	if (ZBX_MOCK_SUCCESS == zbx_mock_parameter("in.value_gen_length", &handle) &&
			ZBX_MOCK_SUCCESS == zbx_mock_uint64(handle, &value_gen_length))
	{
		#define RANG_GEN_REQUEST "{\
			\"data\": {\
				\"steps\": [],\
				\"value_type\": 1,\
				\"value\": \"%s\"\
			},\
			\"request\": \"preprocessing.test\",\
			\"sid\": \"6ed71f17963a881bd010e63b01c39484\"\
		}"
		#define RANG_GEN_RESPONSE_TRUNCATED "{\
			\"response\": \"success\",\
			\"data\": {\
				\"steps\": [],\
				\"truncated\": %s,\
				\"result\": \"%s\",\
				\"original_size\": %zu\
			}\
		}"
		#define RANG_GEN_RESPONSE_UNTRUNCATED "{\
			\"response\": \"success\",\
			\"data\": {\
				\"steps\": [],\
				\"result\": \"%s\"\
			}\
		}"

		size_t append_len, required_length;

		required_length = value_gen_length;
		value_append = zbx_mock_get_parameter_string("in.value_append");
		expected_data_len = zbx_mock_get_parameter_uint64("out.expected_len");
		expected_truncation = zbx_mock_get_parameter_string("out.expected_truncated");

		required_length += append_len = strlen(value_append);
		value_override = (char *)malloc((required_length + 1) * sizeof(char));

		memset(value_override, (int)'a', value_gen_length);

		for (size_t i = 0; i < append_len; i++)
			value_override[i + value_gen_length] = value_append[i];

		value_override[required_length] = '\0';
		zbx_snprintf_alloc(&request_override, &tmp_alloc, &tmp_offset, RANG_GEN_REQUEST, value_override);
		request = request_override;

		tmp_alloc = 0;
		tmp_offset = 0;
		value_override[expected_data_len] = '\0';

		if (0 == strcmp("true", expected_truncation))
		{
			zbx_snprintf_alloc(&response_override, &tmp_alloc, &tmp_offset, RANG_GEN_RESPONSE_TRUNCATED,
					expected_truncation, value_override, required_length);
		}
		else
		{
			zbx_snprintf_alloc(&response_override, &tmp_alloc, &tmp_offset, RANG_GEN_RESPONSE_UNTRUNCATED,
					value_override);
		}

		response = response_override;

		#undef RANG_GEN_REQUEST
		#undef RANG_GEN_RESPONSE_TRUNCATED
		#undef RANG_GEN_RESPONSE_UNTRUNCATED
	}
	else
	{
		request = zbx_mock_get_parameter_string("in.request");

		if (SUCCEED == expected_ret)
			response = zbx_mock_get_parameter_string("out.response");
	}

	if (FAIL == zbx_json_open(request, &jp))
		fail_msg("Invalid request format: %s", zbx_json_strerror());

	returned_ret = trapper_preproc_test_run(&jp, &out, &error);
	if (FAIL == returned_ret)
		printf("trapper_preproc_test_run error: %s\n", error);
	else
		printf("trapper_preproc_test_run output: %s\n", out.buffer);

	zbx_mock_assert_result_eq("Return value", expected_ret, returned_ret);

	if (FAIL == returned_ret)
		zbx_mock_assert_ptr_ne("Error pointer", NULL, error);
	else
		zbx_mock_assert_json_eq("Output", response, out.buffer);

	zbx_free(value_override);
	zbx_free(request_override);
	zbx_free(response_override);

	zbx_free(error);
	zbx_json_free(&out);
}
