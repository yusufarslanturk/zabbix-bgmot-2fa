<?php
/*
** Zabbix
** Copyright (C) 2001-2021 Zabbix SIA
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


/**
 * A helper class for generating HTML snippets.
 */
class CViewHelper {

	/**
	 * Generates </a>&nbsp;<sup>num</sup>" to be used in tables. Null is returned if equal to zero.
	 *
	 * @static
	 *
	 * @param integer $num
	 *
	 * @return mixed
	 */
	public static function showNum($num) {
		if ($num == 0) {
			return null;
		}

		return [SPACE, new CSup($num)];
	}

	/**
	 * Formats Alert in a uniform style for Action logs
	 *
	 * @param array $alert
	 *
	 * @return string Formatted Alert details
	 */
	public static function formattedAlertMessage(array $alert) {
		return ($alert['alerttype'] == ALERT_TYPE_MESSAGE)
			? [
				bold(_('Subject').':'),
				BR(),
				$alert['subject'],
				BR(),
				BR(),
				bold(_('Message').':'),
				BR(),
				zbx_nl2br($alert['message'])
			]
			: [
				bold(_('Command').':'),
				BR(),
				zbx_nl2br($alert['message'])
			];
	}
}
