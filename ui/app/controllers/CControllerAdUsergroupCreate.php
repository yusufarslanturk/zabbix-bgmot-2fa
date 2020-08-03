<?php

class CControllerAdUsergroupCreate extends CController {

	protected function checkInput() {
		$fields = [
			'adgname'	=> 'required|not_empty|db adusrgrp.name',
			'user_groups'	=> 'required|array_db usrgrp.usrgrpid',
			'user_type'	=> 'db adusrgrp.user_type',
			'form_refresh'	=> 'int32'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			switch ($this->getValidationError()) {
				case self::VALIDATION_ERROR:
					$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))
						->setArgument('action', 'adusergrps.edit')
						->getUrl()
					);
					$response->setFormData($this->getInputAll());
					$response->setMessageError(_('Cannot add LDAP group'));
					$this->setResponse($response);
					break;

				case self::VALIDATION_FATAL_ERROR:
					$this->setResponse(new CControllerResponseFatal());
					break;
			}
		}

		return $ret;
	}

	protected function checkPermissions() {
		return ($this->getUserType() == USER_TYPE_SUPER_ADMIN);
	}

	protected function doAction() {
		$usrgrps = $this->getInput('user_groups');
		$aduser_group = [
			'name' => $this->getInput('adgname'),
			'usrgrps' => zbx_toObject($usrgrps, 'usrgrpid'),
			'user_type' => $this->getInput('user_type')
		];

		$result = (bool) API::AdUserGroup()->create($aduser_group);

		if ($result) {
			$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))
				->setArgument('action', 'adusergrps.list')
				->setArgument('page', CPagerHelper::loadPage('adusergrps.list', null))
			);
			$response->setFormData(['uncheck' => '1']);
			$response->setMessageOk(_('LDAP group added'));
		}
		else {
			$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))
				->setArgument('action', 'adusergrps.edit')
			);
			$response->setMessageError(_('Cannot add LDAP group'));
			$response->setFormData($this->getInputAll());
		}

		$this->setResponse($response);
	}
}
