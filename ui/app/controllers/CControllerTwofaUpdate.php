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


class CControllerTwofaUpdate extends CController {

	/**
	 * @var CControllerResponseRedirect
	 */
	private $response;

	protected function init() {
		$this->response = new CControllerResponseRedirect((new CUrl('zabbix.php'))
			->setArgument('action', 'twofa.edit')
			->getUrl()
		);

		$this->disableSIDValidation();
	}

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
			$this->response->setFormData($this->getInputAll());
			$this->setResponse($this->response);
		}

		return $ret;
	}

	/**
	 * Validate DUO 2FA settings.
	 *
	 * @return bool
	 */
	private function validateDuoTwofa() {
		$is_valid = true;
		$config = select_config();
		$req = [];
		$this->getInputs($req, ['2fa_type']);
		$fields = ['2fa_duo_api_hostname', '2fa_duo_integration_key', '2fa_duo_secret_key', '2fa_duo_a_key'];
		$this->getInputs($config, $fields);

		if ($req['2fa_type'] == ZBX_AUTH_2FA_NONE) {
			return $is_valid;
		}

		$settings_changed = array_diff_assoc($config, select_config());

		sdff("+++ settings_changed=");
		sdff($settings_changed);
		if (!$settings_changed) {
			sdff("+++ settings did not change");
			return $is_valid;
		}

		foreach ($fields as $field) {
			if (trim($config[$field]) === '') {
				$this->response->setMessageError(
					_s('Incorrect value for field "%1$s": %2$s.', $field, _('cannot be empty'))
				);
				$is_valid = false;
				break;
			}
		}

		sdff("+++ CControllerTwofaUpdate.php is_valid=");
		sdff($is_valid);
		return $is_valid;
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
		$duo_twofa_valid = $this->validateDuoTwofa();

		if (!$duo_twofa_valid) {
			$this->response->setFormData($this->getInputAll());
			$this->setResponse($this->response);
			return;
		}

		$config = select_config();

		$fields = [
			'2fa_type' => ZBX_AUTH_2FA_NONE
		];

		if ($this->getInput('2fa_type', ZBX_AUTH_2FA_NONE) == ZBX_AUTH_2FA_DUO) {
			$fields += [
				'2fa_duo_api_hostname' =>	'',
				'2fa_duo_integration_key' => 	'',
				'2fa_duo_secret_key' =>		'',
				'2fa_duo_a_key' =>		''
			];
		}

		$data = array_merge($config, $fields);
		//sdff("+++ data before Inputs");
		//sdff($data);
		$this->getInputs($data, array_keys($fields));
		sdff("+++ data Inputs");
		sdff($data);
		$data = array_diff_assoc($data, $config);

		sdff("+++ data['2fa_type']=");
		sdff($data['2fa_type']);
		sdff("+++ getInput('2fa_duo_api_hostname'=");
		sdff($this->getInput('2fa_duo_api_hostname', 'no'));
		if ($data &&
		    (!array_key_exists('2fa_type', $data) || // something other than 2fa_type changed
		    ($data['2fa_type'] == ZBX_AUTH_2FA_DUO &&
		     $this->getInput('2fa_duo_api_hostname', 'no') != 'no') || // User did not just switched to from None
		    ($data['2fa_type'] == ZBX_AUTH_2FA_NONE &&
		     $this->getInput('2fa_duo_api_hostname', 'no') == 'no')) // User did not just switched to None
		   ) {
			sdff("+++ data is not empty");
			sdff($data);
			$result = update_config($data);
			if ($result) {
				if (array_key_exists('2fa_type', $data)) {
					$this->invalidateSessions();
				}

				$this->response->setMessageOk(_('2FA settings updated'));
				add_audit(AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_ZABBIX_CONFIG, _('2FA settings changed'));
			}
			else {
				$this->response->setFormData($this->getInputAll());
				$this->response->setMessageError(_('Cannot update 2FA settings'));
			}
		}
		else {
			$this->response->setFormData($this->getInputAll());
		}

		$this->setResponse($this->response);
	}

	/**
	 * Mark all active GROUP_GUI_ACCESS_INTERNAL sessions, except current user sessions, as ZBX_SESSION_PASSIVE.
	 *
	 * @return bool
	 */
	private function invalidateSessions() {
		$result = true;
		$internal_auth_user_groups = API::UserGroup()->get([
			'output' => [],
			'filter' => [
				'gui_access' => GROUP_GUI_ACCESS_INTERNAL
			],
			'preservekeys' => true
		]);

		$internal_auth_users = API::User()->get([
			'output' => [],
			'usrgrpids' => array_keys($internal_auth_user_groups),
			'preservekeys' => true
		]);
		unset($internal_auth_users[CWebUser::$data['userid']]);

		if ($internal_auth_users) {
			DBstart();
			$result = DB::update('sessions', [
				'values' => ['status' => ZBX_SESSION_PASSIVE],
				'where' => ['userid' => array_keys($internal_auth_users)]
			]);
			$result = DBend($result);
		}

		return $result;
	}
}
