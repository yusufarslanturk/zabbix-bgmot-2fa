<?php
/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
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


class CControllerTwofaEdit extends CController {

	protected function init() {
		$this->disableSIDValidation();
	}

	/**
	 * Validate user input.
	 *
	 * @return bool
	 */
	protected function checkInput() {
		$fields = [
			'2fa_type' =>			'db config.2fa_type',
			'form_refresh' =>		'string',
			// actions
			'update' =>			'string',
			// DUO 2FA
			'2fa_duo_api_hostname' =>	'db config.2fa_duo_api_hostname',
			'2fa_duo_integration_key' => 	'db config.2fa_duo_integration_key',
			'2fa_duo_secret_key' =>		'db config.2fa_duo_secret_key',
			'2fa_duo_a_key' =>		'db config.2fa_duo_a_key'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			$this->setResponse(new CControllerResponseFatal());
		}

		return $ret;
	}

	/**
	 * Validate is user allowed to change configuration.
	 *
	 * @return bool
	 */
	protected function checkPermissions() {
		return $this->getUserType() == USER_TYPE_SUPER_ADMIN;
	}

	protected function doAction() {
		$data = [
			'action_submit' => 'twofa.update',
			'form_refresh' => 0
		];

		$this->getInputs($data, [
			'form_refresh',
			'2fa_type',
			'2fa_duo_api_hostname',
			'2fa_duo_integration_key',
			'2fa_duo_secret_key',
			'2fa_duo_a_key',
		]);

		$data += select_config();

		$response = new CControllerResponseData($data);
		$response->setTitle(twofa2str($data['2fa_type']));
		$this->setResponse($response);
	}
}
