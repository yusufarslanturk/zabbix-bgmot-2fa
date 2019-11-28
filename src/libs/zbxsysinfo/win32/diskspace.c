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
#include "sysinfo.h"
#include "zbxjson.h"
#include "zbxalgo.h"
#include "log.h"

typedef struct
{
	char		fsname[MAX_STRING_LEN];
	char		fstype[MAX_STRING_LEN];
	char		fsdrivetype[MAX_STRING_LEN];
	zbx_uint64_t	total;
	zbx_uint64_t	not_used;
	zbx_uint64_t	used;
	double		pfree;
	double		pused;
}
zbx_wmpoint_t;

static int	get_fs_size_stat(const char *fs, zbx_uint64_t *total, zbx_uint64_t *not_used,
		zbx_uint64_t *used, double *pfree, double *pused, char **error)
{
	wchar_t 	*wpath;
	ULARGE_INTEGER	freeBytes, totalBytes;

	wpath = zbx_utf8_to_unicode(fs);
	if (0 == GetDiskFreeSpaceEx(wpath, &freeBytes, &totalBytes, NULL))
	{
		zbx_free(wpath);
		*error = zbx_dsprintf(NULL, "Cannot obtain filesystem information: %s",
				strerror_from_system(GetLastError()));
		zabbix_log(LOG_LEVEL_DEBUG,"%s failed with error: %s",__func__, *error);
		return SYSINFO_RET_FAIL;
	}
	zbx_free(wpath);

	*total = totalBytes.QuadPart;
	*not_used = freeBytes.QuadPart;
	*used = totalBytes.QuadPart - freeBytes.QuadPart;
	*pfree = (double)(__int64)freeBytes.QuadPart * 100. / (double)(__int64)totalBytes.QuadPart;
	*pused = (double)((__int64)totalBytes.QuadPart - (__int64)freeBytes.QuadPart) * 100. /
			(double)(__int64)totalBytes.QuadPart;

	return SYSINFO_RET_OK;

}

static int	vfs_fs_size(AGENT_REQUEST *request, AGENT_RESULT *result, HANDLE timeout_event)
{
	char		*path, *mode;
	char		*error;
	zbx_uint64_t	total, used, free;
	double		pused,pfree;

	/* 'timeout_event' argument is here to make the vfs_fs_size() prototype as required by */
	/* zbx_execute_threaded_metric() on MS Windows */
	ZBX_UNUSED(timeout_event);

	if (2 < request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Too many parameters."));
		return SYSINFO_RET_FAIL;
	}

	path = get_rparam(request, 0);
	mode = get_rparam(request, 1);

	if (NULL == path || '\0' == *path)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid first parameter."));
		return SYSINFO_RET_FAIL;
	}

	if (SYSINFO_RET_OK != get_fs_size_stat(path, &total, &free, &used, &pfree, &pused, &error))
	{
		SET_MSG_RESULT(result, error);
		return SYSINFO_RET_FAIL;
	}

	if (NULL == mode || '\0' == *mode || 0 == strcmp(mode, "total"))
		SET_UI64_RESULT(result, total);
	else if (0 == strcmp(mode, "free"))
		SET_UI64_RESULT(result, free);
	else if (0 == strcmp(mode, "used"))
		SET_UI64_RESULT(result, used);
	else if (0 == strcmp(mode, "pfree"))
		SET_DBL_RESULT(result, pfree);
	else if (0 == strcmp(mode, "pused"))
		SET_DBL_RESULT(result, pused);
	else
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid second parameter."));
		return SYSINFO_RET_FAIL;
	}

	return SYSINFO_RET_OK;
}

int	VFS_FS_SIZE(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	return zbx_execute_threaded_metric(vfs_fs_size, request, result);
}

static const char	*get_drive_type_string(UINT type)
{
	switch (type)
	{
		case DRIVE_UNKNOWN:
			return "unknown";
		case DRIVE_NO_ROOT_DIR:
			return "norootdir";
		case DRIVE_REMOVABLE:
			return "removable";
		case DRIVE_FIXED:
			return "fixed";
		case DRIVE_REMOTE:
			return "remote";
		case DRIVE_CDROM:
			return "cdrom";
		case DRIVE_RAMDISK:
			return "ramdisk";
		default:
			THIS_SHOULD_NEVER_HAPPEN;
			return "unknown";
	}
}

static void	add_fs_to_json(zbx_wmpoint_t *mntpoint, struct zbx_json *j, const char *fsname_tag,
		const char *fstype_tag,	const char *fsdrivetype_tag, int metrics)
{
	zbx_json_addobject(j, NULL);
	zbx_json_addstring(j, fsname_tag, mntpoint->fsname, ZBX_JSON_TYPE_STRING);
	zbx_json_addstring(j, fstype_tag, mntpoint->fstype, ZBX_JSON_TYPE_STRING);
	zbx_json_addstring(j, fsdrivetype_tag, mntpoint->fsdrivetype, ZBX_JSON_TYPE_STRING);

	if (1 == metrics)
	{
		zbx_json_adduint64(j, ZBX_SYSINFO_TAG_TOTAL, mntpoint->total);
		zbx_json_adduint64(j, ZBX_SYSINFO_TAG_FREE, mntpoint->not_used);
		zbx_json_adduint64(j, ZBX_SYSINFO_TAG_USED, mntpoint->used);
		zbx_json_addfloat(j, ZBX_SYSINFO_TAG_PFREE, mntpoint->pfree);
		zbx_json_addfloat(j, ZBX_SYSINFO_TAG_PUSED, mntpoint->pused);
	}

	zbx_json_close(j);
}

static void	add_fs_to_vector(zbx_vector_ptr_t *mntpoints, wchar_t *path, int metrics)
{
	wchar_t		fs_name[MAX_PATH + 1], *long_path = NULL;
	char		*utf8;
	size_t		sz;
	zbx_wmpoint_t	*mntpoint;
	zbx_uint64_t	total, not_used, used;
	double		pfree, pused;
	char 		*error;


	utf8 = zbx_unicode_to_utf8(path);
	sz = strlen(utf8);

	if (0 < sz && '\\' == utf8[--sz])
		utf8[sz] = '\0';

	if (0 != metrics && SYSINFO_RET_OK != get_fs_size_stat(utf8, &total, &not_used, &used, &pfree, &pused,&error))
	{
		zbx_free(error);
		zbx_free(utf8);
		return;
	}

	mntpoint = (zbx_wmpoint_t *)zbx_malloc(NULL, sizeof(zbx_wmpoint_t));
	zbx_strlcpy(mntpoint->fsname, utf8, MAX_STRING_LEN);

	zbx_free(utf8);

	/* add \\?\ prefix if path exceeds MAX_PATH */
	if (MAX_PATH < (sz = wcslen(path) + 1) && 0 != wcsncmp(path, L"\\\\?\\", 4))
	{
		/* allocate memory buffer enough to hold null-terminated path and prefix */
		long_path = (wchar_t*)zbx_malloc(long_path, (sz + 4) * sizeof(wchar_t));

		long_path[0] = L'\\';
		long_path[1] = L'\\';
		long_path[2] = L'?';
		long_path[3] = L'\\';

		memcpy(long_path + 4, path, sz * sizeof(wchar_t));
		path = long_path;
	}

	if (FALSE != GetVolumeInformation(path, NULL, 0, NULL, NULL, NULL, fs_name, ARRSIZE(fs_name)))
	{
		utf8 = zbx_unicode_to_utf8(fs_name);
		zbx_strlcpy(mntpoint->fstype, utf8, MAX_STRING_LEN);
		zbx_free(utf8);
	}
	else
		zbx_strlcpy(mntpoint->fstype, "UNKNOWN", MAX_STRING_LEN);

	zbx_strlcpy(mntpoint->fsdrivetype, get_drive_type_string(GetDriveType(path)), MAX_STRING_LEN);

	if (0 != metrics)
	{
		mntpoint->total = total;
		mntpoint->not_used = not_used;
		mntpoint->used = used;
		mntpoint->pfree = pfree;
		mntpoint->pused = pused;
	}
	zbx_vector_ptr_append(mntpoints, mntpoint);

	zbx_free(long_path);
}

static void	zbx_wmpoints_free(zbx_wmpoint_t *mpoint)
{
	zbx_free(mpoint);
}

int	VFS_FS_DISCOVERY(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	wchar_t			*buffer = NULL, volume_name[MAX_PATH + 1], *p;
	DWORD			size_dw;
	size_t			sz;
	struct zbx_json		j;
	HANDLE			volume;
	int			ret;
	zbx_vector_ptr_t	mntpoints;
	zbx_wmpoint_t		*mntpoint;
	int 			i;

	/* make an initial call to GetLogicalDriveStrings() to get the necessary size into the dwSize variable */
	if (0 == (size_dw = GetLogicalDriveStrings(0, buffer)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Cannot obtain necessary buffer size from system."));
		return SYSINFO_RET_FAIL;
	}

	zbx_vector_ptr_create(&mntpoints);
	zbx_json_initarray(&j, ZBX_JSON_STAT_BUF_LEN);

	buffer = (wchar_t *)zbx_malloc(buffer, (size_dw + 1) * sizeof(wchar_t));

	/* make a second call to GetLogicalDriveStrings() to get the actual data we require */
	if (0 == (size_dw = GetLogicalDriveStrings(size_dw, buffer)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Cannot obtain a list of filesystems."));
		ret = SYSINFO_RET_FAIL;
		goto out;
	}

	/* add drive letters */
	for (p = buffer, sz = wcslen(p); sz > 0; p += sz + 1, sz = wcslen(p))
		add_fs_to_vector(&mntpoints,p,1);

	if (INVALID_HANDLE_VALUE == (volume = FindFirstVolume(volume_name, ARRSIZE(volume_name))))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Cannot find a volume."));
		ret = SYSINFO_RET_FAIL;
		goto out;
	}

	/* search volumes for mount point folder paths */
	do
	{
		while (FALSE == GetVolumePathNamesForVolumeName(volume_name, buffer, size_dw, &size_dw))
		{
			DWORD last_error;

			last_error = GetLastError();

			if (ERROR_MORE_DATA != last_error)
			{
				FindVolumeClose(volume);
				SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Cannot obtain a list of filesystems: %s",
						strerror_from_system(last_error)));
				ret = SYSINFO_RET_FAIL;
				goto out;
			}

			buffer = (wchar_t*)zbx_realloc(buffer, size_dw * sizeof(wchar_t));
		}

		for (p = buffer, sz = wcslen(p); sz > 0; p += sz + 1, sz = wcslen(p))
		{
			/* add mount point folder paths but skip drive letters */
			if (3 < sz)
				add_fs_to_vector(&mntpoints,p,1);
		}

	} while (FALSE != FindNextVolume(volume, volume_name, ARRSIZE(volume_name)));

	for (i = 0; i < mntpoints.values_num; i++)
	{
		mntpoint = (zbx_wmpoint_t *)mntpoints.values[i];
		add_fs_to_json(mntpoint, &j, ZBX_LLD_MACRO_FSNAME, ZBX_LLD_MACRO_FSTYPE, ZBX_LLD_MACRO_FSDRIVETYPE, 0);
	}

	if (ERROR_NO_MORE_FILES != GetLastError())
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Cannot obtain complete list of filesystems."));
		ret = SYSINFO_RET_FAIL;
	}
	else
	{
		zbx_json_close(&j);
		SET_STR_RESULT(result, zbx_strdup(NULL, j.buffer));
		ret = SYSINFO_RET_OK;
	}

	FindVolumeClose(volume);
out:
	zbx_json_free(&j);
	zbx_free(buffer);
	zbx_vector_ptr_clear_ext(&mntpoints, (zbx_clean_func_t)zbx_wmpoints_free);
	zbx_vector_ptr_destroy(&mntpoints);

	return ret;
}

static int	vfs_fs_get(AGENT_REQUEST *request, AGENT_RESULT *result,  HANDLE timeout_event)
{
	wchar_t			*buffer = NULL, volume_name[MAX_PATH + 1], *p;
	DWORD			size_dw;
	size_t			sz;
	struct zbx_json		j;
	HANDLE			volume;
	int			ret;
	zbx_vector_ptr_t	mntpoints;
	zbx_wmpoint_t		*mntpoint;
	int			i;
	char 			*mpoint;

	/* 'timeout_event' argument is here to make the vfs_fs_size() prototype as required by */
	/* zbx_execute_threaded_metric() on MS Windows */
	ZBX_UNUSED(timeout_event);
	zbx_json_initarray(&j, ZBX_JSON_STAT_BUF_LEN);
	zbx_vector_ptr_create(&mntpoints);

	for (i = 0; i < 2; i++)
	{
		/* make an initial call to GetLogicalDriveStrings() to get the necessary size into the dwSize variable */
		if (0 == (size_dw = GetLogicalDriveStrings(0, buffer)))
		{
			SET_MSG_RESULT(result, zbx_strdup(NULL, "Cannot obtain necessary buffer size from system."));
			ret = SYSINFO_RET_FAIL;
			goto out;
		}

		buffer = (wchar_t *)zbx_malloc(buffer, (size_dw + 1) * sizeof(wchar_t));

		/* make a second call to GetLogicalDriveStrings() to get the actual data we require */
		if (0 == (size_dw = GetLogicalDriveStrings(size_dw, buffer)))
		{
			SET_MSG_RESULT(result, zbx_strdup(NULL, "Cannot obtain a list of filesystems."));
			ret = SYSINFO_RET_FAIL;
			goto out;
		}

		/* add drive letters */
		for (p = buffer, sz = wcslen(p); sz > 0; p += sz + 1, sz = wcslen(p))
		{
			int idx;

			if (0 != i)
			{
				mpoint = zbx_unicode_to_utf8(p);
				sz = strlen(mpoint);

				if (0 < sz && '\\' == mpoint[--sz])
					mpoint[sz] = '\0';
				if (FAIL != (idx = zbx_vector_ptr_search(&mntpoints,
						mpoint, ZBX_DEFAULT_STR_COMPARE_FUNC)))
				{
					mntpoint = (zbx_wmpoint_t *)mntpoints.values[idx];
					add_fs_to_json(mntpoint, &j, ZBX_SYSINFO_TAG_FSNAME, ZBX_SYSINFO_TAG_FSTYPE,
							ZBX_SYSINFO_TAG_FSDRIVETYPE, 1);
				}
				zbx_free(mpoint);
			}
			else
			{
				// add drive letters with sizes to array
				add_fs_to_vector(&mntpoints,p,1);
			}
		}

		if (INVALID_HANDLE_VALUE == (volume = FindFirstVolume(volume_name, ARRSIZE(volume_name))))
		{
			SET_MSG_RESULT(result, zbx_strdup(NULL, "Cannot find a volume."));
			ret = SYSINFO_RET_FAIL;
			goto out;
		}

		/* search volumes for mount point folder paths */
		do
		{
			while (FALSE == GetVolumePathNamesForVolumeName(volume_name, buffer, size_dw, &size_dw))
			{
				DWORD last_error;

				last_error = GetLastError();

				if (ERROR_MORE_DATA != last_error)
				{
					FindVolumeClose(volume);
					SET_MSG_RESULT(result, zbx_dsprintf(NULL,
							"Cannot obtain a list of filesystems: %s",
							strerror_from_system(last_error)));
					ret = SYSINFO_RET_FAIL;
					goto out;
				}

				buffer = (wchar_t*)zbx_realloc(buffer, size_dw * sizeof(wchar_t));
			}

			for (p = buffer, sz = wcslen(p); sz > 0; p += sz + 1, sz = wcslen(p))
			{
				int idx;

				/* add mount point folder paths but skip drive letters */
				if (3 < sz)
				{
					if (0 != i)
					{
						mpoint = zbx_unicode_to_utf8(p);
						sz = strlen(mpoint);

						if (0 < sz && '\\' == mpoint[--sz])
							mpoint[sz] = '\0';

						if (FAIL != (idx = zbx_vector_ptr_search(&mntpoints,
									mpoint, ZBX_DEFAULT_STR_COMPARE_FUNC)))
						{
							mntpoint = (zbx_wmpoint_t *)mntpoints.values[idx];
							add_fs_to_json(mntpoint, &j, ZBX_SYSINFO_TAG_FSNAME,
									ZBX_SYSINFO_TAG_FSTYPE,
									ZBX_SYSINFO_TAG_FSDRIVETYPE, 1);
						}
						zbx_free(mpoint);
					}
					else
						add_fs_to_vector(&mntpoints,p,1);
				}
			}

		} while (FALSE != FindNextVolume(volume, volume_name, ARRSIZE(volume_name)));

		if (0 != i)
		{
			if (ERROR_NO_MORE_FILES != GetLastError())
			{
				SET_MSG_RESULT(result, zbx_strdup(NULL, "Cannot obtain complete list of filesystems."));
				ret = SYSINFO_RET_FAIL;
			}
			else
			{
				zbx_json_close(&j);
				SET_STR_RESULT(result, zbx_strdup(NULL, j.buffer));
				ret = SYSINFO_RET_OK;
			}
		}

		FindVolumeClose(volume);
		zbx_free(buffer);
	}
out:
	zbx_json_free(&j);
	zbx_free(buffer);
	zbx_vector_ptr_clear_ext(&mntpoints, (zbx_clean_func_t)zbx_wmpoints_free);
	zbx_vector_ptr_destroy(&mntpoints);

	return ret;
}

int	VFS_FS_GET(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	return zbx_execute_threaded_metric(vfs_fs_get, request, result);
}
