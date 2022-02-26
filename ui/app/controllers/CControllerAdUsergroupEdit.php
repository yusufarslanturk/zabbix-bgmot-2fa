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
			'user_groups'     => 'not_empty|array_db usrgrp.name',
			'roleid'          => 'db adusrgrp.roleid',
			'form_refresh'    => 'int32'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			$this->setResponse(new CControllerResponseFatal());
		}

		return $ret;
	}

	protected function checkPermissions() {
		if (!$this->checkAccess(CRoleHelper::UI_ADMINISTRATION_USERS)) {
			return false;
		}

		if ($this->hasInput('adusrgrpid')) {
			$db_aduser_group = API::AdUserGroup()->get([
				'output' => ['adusrgrpid', 'name', 'roleid'],
				'selectUsrgrps' => ['usrgrpid'],
				'selectRole' => ['name', 'type'],
				'adusrgrpids' => $this->getInput('adusrgrpid'),
				'editable' => true
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
			'name' => '',
			'roleid' => '',
			'role' => [],
			'user_type' => '',
			'form_refresh' => 0,
			'action' => $this->getAction()
		];
		$user_groups = [];

		// get values from the dabatase
		if ($this->hasInput('adusrgrpid')) {
			$data['adusrgrpid'] = $this->getInput('adusrgrpid');
			$data['name'] = $this->db_aduser_group['name'];
			$user_groups = zbx_objectValues($this->db_aduser_group['usrgrps'], 'usrgrpid');
			if (!$this->getInput('form_refresh', 0)) {
				$data['roleid'] = $this->db_aduser_group['roleid'];
				$data['user_type'] = $this->db_aduser_group['role']['type'];
				$data['role'] = [['id' => $data['roleid'], 'name' => $this->db_aduser_group['role']['name']]];
			}
		}
		else {
			$data['roleid'] = $this->getInput('roleid', '');
		}

		// overwrite with input variables
		$this->getInputs($data, ['name', 'roleid', 'form_refresh']);

		if ($data['form_refresh'] != 0) {
			$user_groups = $this->getInput('user_groups', []);
			$data['name'] = $this->getInput('adgname', '');
		}

		$data['groups'] = $user_groups
			? API::UserGroup()->get([
				'output' => ['usrgrpid', 'name'],
				'usrgrpids' => $user_groups
			])
			: [];
		CArrayHelper::sort($data['groups'], ['name']);
		$data['groups'] = CArrayHelper::renameObjectsKeys($data['groups'], ['usrgrpid' => 'id']);

		if ($data['form_refresh'] && $this->hasInput('roleid')) {
			$roles = API::Role()->get([
				'output' => ['name', 'type'],
				'roleids' => $data['roleid']
			]);

			if ($roles) {
				$data['role'] = [['id' => $data['roleid'], 'name' => $roles[0]['name']]];
				$data['user_type'] = $roles[0]['type'];
			}
		}

		if ($data['user_type'] == USER_TYPE_SUPER_ADMIN) {
			$data['groups_rights'] = [
				'0' => [
					'permission' => PERM_READ_WRITE,
					'name' => '',
					'grouped' => '1'
				]
			];
		}
		else {
			$data['groups_rights'] = collapseHostGroupRights(getHostGroupsRights($user_groups));
		}

		$data['modules'] = API::Module()->get([
			'output' => ['id'],
			'filter' => ['status' => MODULE_STATUS_ENABLED],
			'preservekeys' => true
		]);

		$response = new CControllerResponseData($data);
		$response->setTitle(_('Configuration of LDAP groups'));
		$this->setResponse($response);
	}
}
