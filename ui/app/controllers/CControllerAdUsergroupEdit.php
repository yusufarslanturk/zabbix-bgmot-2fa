<?php

class CControllerAdUsergroupEdit extends CController {

	/**
	 * @var array AD group data from database.
	 */
	private $db_aduser_group = [];

	protected function init() {
		$this->disableSIDValidation();
	}

	protected function checkInput() {
		$fields = [
			'adusrgrpid'      => 'db adusrgrp.adusrgrpid',
			'adgname'         => 'db adusrgrp.name',
			'adgroup_groupid' => 'db adgroups_groups.id',
			'user_groups'     => 'array_db usrgrp.name',
			'user_type'      => 'db adusrgrp.user_type',

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

		if ($this->hasInput('adusrgrpid')) {
			$db_aduser_group = API::AdUserGroup()->get([
				'output' => ['adusrgrpid', 'name', 'user_type'],
				'adusrgrpids' => getRequest('adusrgrpid')
			]);

			if (!$db_aduser_group) {
				return false;
			}

			$this->db_aduser_group = $db_aduser_group[0];
		}

		return true;
	}

	protected function doAction() {
		// default values
		$db_defaults = DB::getDefaults('adusrgrp');
		$data = [
			'adusrgrpid' => 0,
			'name' => $db_defaults['name'],
			'user_type' => $db_defaults['user_type'],
			'form_refresh' => 0
		];

		// get values from the dabatase
		if ($this->hasInput('adusrgrpid')) {
			$data['adusrgrpid'] = $this->db_aduser_group['adusrgrpid'];
			$data['name'] = $this->db_aduser_group['name'];
			$data['user_type'] = $this->db_aduser_group['user_type'];
		}

		// overwrite with input variables
		$this->getInputs($data, ['name', 'user_type']);

		$data['groups'] = API::UserGroup()->get([
			'output' => ['usrgrpid', 'name'],
			'adusrgrpids' => getRequest('adusrgrpid', 0)
		]);
		order_result($data['groups'], 'name');

		$response = new CControllerResponseData($data);
		$response->setTitle(_('Configuration of LDAP groups'));
		$this->setResponse($response);
	}
}
