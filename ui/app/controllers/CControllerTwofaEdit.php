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
		return $this->checkAccess(CRoleHelper::UI_ADMINISTRATION_AUTHENTICATION);
	}

	protected function doAction() {
		$data = [
			'action_submit' => 'twofa.update',
			'form_refresh' => 0
		];

		$twofa_params = [
			CTwofaHelper::TWOFA_TYPE,
			CTwofaHelper::TWOFA_DUO_API_HOSTNAME,
			CTwofaHelper::TWOFA_DUO_INTEGRATION_KEY,
			CTwofaHelper::TWOFA_DUO_SECRET_KEY,
			CTwofaHelper::TWOFA_DUO_A_KEY
		];

		$twofa = [];
		foreach ($twofa_params as $param) {
			$twofa[$param] = CTwofaHelper::get($param);
		}

		if ($this->hasInput('form_refresh')) {
			$this->getInputs($data, [
				'form_refresh',
				'2fa_type',
				'2fa_duo_api_hostname',
				'2fa_duo_integration_key',
				'2fa_duo_secret_key',
				'2fa_duo_a_key',
			]);

			$data += $twofa;
		}
		else {
			$data += $twofa;
		}

		$response = new CControllerResponseData($data);
		$response->setTitle(twofa2str($data['2fa_type']));
		$this->setResponse($response);
	}
}
