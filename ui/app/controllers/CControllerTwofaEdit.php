<?php

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

		if ($this->hasInput('form_refresh')) {
			$this->getInputs($data, [
				'form_refresh',
				'2fa_type',
				'2fa_duo_api_hostname',
				'2fa_duo_integration_key',
				'2fa_duo_secret_key',
				'2fa_duo_a_key',
			]);

			$data += select_config();
		}
		else {
			$data += select_config();
		}

		$response = new CControllerResponseData($data);
		$response->setTitle(twofa2str($data['2fa_type']));
		$this->setResponse($response);
	}
}
