<?php

class CControllerAdUsergroupCreate extends CController {

	protected function checkInput() {
		$fields = [
			'adgname'	=> 'required|not_empty|db adusrgrp.name',
			'user_groups'	=> 'required|array_db usrgrp.usrgrpid',
			'roleid'        => 'required|db adusrgrp.roleid',
			'form_refresh'	=> 'int32'
		];

		$ret = $this->validateInput($fields);
		$error = $this->GetValidationError();

		if (!$ret) {
			switch ($error) {
				case self::VALIDATION_ERROR:
					$response = new CControllerResponseRedirect('zabbix.php?action=adusergrps.edit');
					$response->setFormData($this->getInputAll());
					CMessageHelper::setErrorTitle(_('Cannot add LDAP group'));
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
		return $this->checkAccess(CRoleHelper::UI_ADMINISTRATION_USERS);
	}

	protected function doAction() {
		$aduser_group = [
			'name' => $this->getInput('adgname'),
			'usrgrps' => zbx_toObject($this->getInput('user_groups'), 'usrgrpid'),
			'roleid' => $this->getInput('roleid')
		];

		$result = (bool) API::AdUserGroup()->create($aduser_group);

		if ($result) {
			$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))
				->setArgument('action', 'adusergrps.list')
				->setArgument('page', CPagerHelper::loadPage('adusergrps.list', null))
			);
			$response->setFormData(['uncheck' => '1']);
			CMessageHelper::setSuccessTitle(_('LDAP group added'));
		}
		else {
			$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))
				->setArgument('action', 'adusergrps.edit')
			);
			$response->setFormData($this->getInputAll());
			CMessageHelper::setErrorTitle(_('Cannot add LDAP group'));
		}

		$this->setResponse($response);
	}
}
