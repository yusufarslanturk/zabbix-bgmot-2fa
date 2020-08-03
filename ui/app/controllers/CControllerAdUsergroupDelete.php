<?php

class CControllerAdUsergroupDelete extends CController {

	protected function init() {
		$this->disableSIDValidation();
	}

	protected function checkInput() {
		$fields = [
			'adusrgrpid'      => 'array_db adusrgrp.adusrgrpid',
			'form_refresh'    => 'int32'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			$this->setResponse(new CControllerResponseFatal());
		}

		return $ret;
	}

	protected function checkPermissions() {
		if ($this->getUserType() != USER_TYPE_SUPER_ADMIN) {
			return false;
		}

		return true;
	}

	protected function doAction() {
		$adusrgrpids = $this->getInput('adusrgrpid');

		$result = (bool) API::AdUserGroup()->delete($adusrgrpids);

		$deleted = count($adusrgrpids);

		$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))
			->setArgument('action', 'adusergrps.list')
			->setArgument('page', CPagerHelper::loadPage('adusergrps.list', null))
		);

		if ($result) {
			$response->setFormData(['uncheck' => '1']);
			$response->setMessageOk(_n('LDAP group deleted', 'LDAP groups deleted', $deleted));
		}
		else {
			$response->setMessageError(_n('Cannot delete LDAP group', 'Cannot delete LDAP groups', $deleted));
		}

		$this->setResponse($response);
	}
}
