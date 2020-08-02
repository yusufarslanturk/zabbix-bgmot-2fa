<?php

class CControllerAdUsergroupList extends CController {

	protected function init() {
		$this->disableSIDValidation();
	}

	protected function checkInput() {
		$fields = [
			'filter_name' => 'string',
			'filter_set' => 'in 1',
			'filter_rst' => 'in 1',
			'sort' => 'in name',
			'sortorder' => 'in '.ZBX_SORT_DOWN.','.ZBX_SORT_UP,
			'page' => 'ge 1',
			'uncheck' => 'in 1'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			$this->setResponse(new CControllerResponseFatal());
		}

		return $ret;
	}

	protected function checkPermissions() {
		return ($this->getUserType() == USER_TYPE_SUPER_ADMIN);
	}

	protected function doAction() {
		$sort_field = $this->getInput('sort', CProfile::get('web.adusergroup.sort', 'name'));
		$sort_order = $this->getInput('sortorder', CProfile::get('web.adusergroup.sortorder', ZBX_SORT_UP));

		CProfile::update('web.adusergroup.sort', $sort_field, PROFILE_TYPE_STR);
		CProfile::update('web.adusergroup.sortorder', $sort_order, PROFILE_TYPE_STR);

		// filter
		if ($this->hasInput('filter_set')) {
			CProfile::update('web.adusergroup.filter_name', $this->getInput('filter_name', ''), PROFILE_TYPE_STR);
		}
		elseif ($this->hasInput('filter_rst')) {
			CProfile::delete('web.adusergroup.filter_name');
		}

		// Prepare data for view.
		$filter = [
			'name' => CProfile::get('web.adusergroup.filter_name', '')
		];

		$config = select_config();

		$data = [
			'uncheck' => $this->hasInput('uncheck'),
			'sort' => $sort_field,
			'sortorder' => $sort_order,
			'filter' => $filter,
			'profileIdx' => 'web.adusergroup.filter',
			'active_tab' => CProfile::get('web.adusergroup.filter.active', 1),
			'adusergroups' => API::AdUserGroup()->get([
				'output' => ['adusrgrpid', 'name', 'user_type'],
				'search' => ['name' => ($filter['name'] !== '') ? $filter['name'] : null],
				'adusrgrpids' => getRequest('adusrgrpid'),
				'sortfield' => $sort_field,
				'limit' => $config['search_limit'] + 1
			])
		];

		// data sort and pager
		CArrayHelper::sort($data['adusergroups'], [['field' => $sort_field, 'order' => $sort_order]]);

		$page_num = getRequest('page', 1);
		CPagerHelper::savePage('adusergroup.list', $page_num);
		$data['paging'] = CPagerHelper::paginate($page_num, $data['adusergroups'], $sort_order,
			(new CUrl('zabbix.php'))->setArgument('action', $this->getAction())
		);

		foreach ($data['adusergroups'] as &$adusergroup) {
			$adusergroup['groups'] = API::UserGroup()->get([
				'output' => ['usrgrpid', 'name', 'gui_access', 'users_status'],
				'adusrgrpids' => $adusergroup['adusrgrpid']
			]);
			CArrayHelper::sort($adusergroup['groups'], ['name']);

			$usergroup['group_cnt'] = count($adusergroup['groups']);
			if ($usergroup['group_cnt'] > $config['max_in_table']) {
				$usergroup['groups'] = array_slice($usergroup['groups'], 0, $config['max_in_table']);
			}
		}
		unset($adusergroup);

		$response = new CControllerResponseData($data);

		$response->setTitle(_('Configuration of AD groups'));
		$this->setResponse($response);
	}
}
