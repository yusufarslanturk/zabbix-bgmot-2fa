<?php

class CControllerAdUsergroupUpdate extends CController {

	protected function checkInput() {
		$fields = [
			'adusrgrpid'	=> 'required|db adusrgrp.adusrgrpid',
			'adgname'	=> 'required|not_empty|db adusrgrp.name',
			'user_groups'	=> 'required|array_db usrgrp.usrgrpid',
			'roleid'	=> 'required|db adusrgrp.roleid',
			'form_refresh'	=> 'int32'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			switch ($this->getValidationError()) {
				case self::VALIDATION_ERROR:
					$response = new CControllerResponseRedirect('zabbix.php?action=adusergrps.edit');
					$response->setFormData($this->getInputAll());
					CMessageHelper::setErrorTitle(_('Cannot update LDAP group'));
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
		if (!$this->checkAccess(CRoleHelper::UI_ADMINISTRATION_USERS)) {
			return false;
		}

		return (bool) API::UserGroup()->get([
			'output' => [],
			'usrgrpids' => $this->getInput('usrgrpids') 
		]);
	}

	protected function doAction() {
		$usrgrps = $this->getInput('user_groups');
		$aduser_group = [
			'adusrgrpid' => $this->getInput('adusrgrpid'),
			'name' => $this->getInput('adgname'),
			'usrgrps' => zbx_toObject($usrgrps, 'usrgrpid'),
			'roleid' => $this->getInput('roleid')
		];

		$result = (bool) API::AdUserGroup()->update($aduser_group);

		if ($result) {
			$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))
				->setArgument('action', 'adusergrps.list')
				->setArgument('page', CPagerHelper::loadPage('adusergrps.list', null))
			);
			$response->setFormData(['uncheck' => '1']);
			CMessageHelper::setSuccessTitle(_('LDAP group updated'));
		}
		else {
			$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))
				->setArgument('action', 'adusergrps.edit')
			);
			$response->setFormData($this->getInputAll());
			CMessageHelper::setErrorTitle(_('Cannot update LDAP group'));
		}

		$this->setResponse($response);
	}
}
