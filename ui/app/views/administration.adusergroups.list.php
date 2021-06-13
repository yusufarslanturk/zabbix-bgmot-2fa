<?php

$widget = (new CWidget())
	->setTitle(_('LDAP groups'))
	->setControls((new CTag('nav', true,
		(new CList())
			->addItem(new CRedirectButton(_('Create LDAP group'),
				(new CUrl('zabbix.php'))->setArgument('action', 'adusergrps.edit'))
			)
		))->setAttribute('aria-label', _('Content controls'))
	)
	->addItem((new CFilter((new CUrl('zabbix.php'))->setArgument('action', 'adusergrps.list')))
		->addVar('action', 'adusergrps.list')
		->setProfile($data['profileIdx'])
		->setActiveTab($data['active_tab'])
		->addFilterTab(_('Filter'), [
			(new CFormList())->addRow(_('Name'),
				(new CTextBox('filter_name', $data['filter']['name']))
					->setWidth(ZBX_TEXTAREA_FILTER_SMALL_WIDTH)
					->setAttribute('autofocus', 'autofocus')
			)
		])
	);

// create form
$adGroupsForm = (new CForm())
	->setName('adGroupsForm')
	->setId('adusergrps');

// create AD group table
$adGroupTable = (new CTableInfo())
	->setHeader([
		(new CColHeader(
			(new CCheckBox('all_adgroups'))->onClick("checkAll('".$adGroupsForm->getName()."','all_adgroups','adusrgrpid');")
		))->addClass(ZBX_STYLE_CELL_WIDTH),
		make_sorting_header(_('Name'), 'name', $data['sort'], $data['sortorder'],
			(new CUrl('zabbix.php'))
				->setArgument('action', 'adusergrps.list')
				->getUrl()
		),
		_('User groups'),
		_('User role')
	]);

foreach ($this->data['adusergroups'] as $adusrgrp) {
	$adGroupId = $adusrgrp['adusrgrpid'];

	if (isset($adusrgrp['groups'])) {
		$adGroupUserGroups = $adusrgrp['groups'];
		order_result($adGroupUserGroups, 'name');

		$userGroups = [];

		foreach ($adGroupUserGroups as $usergroup) {
			if ($userGroups) {
				$userGroups[] = ', ';
			}

			$userGroups[] = (new CLink($usergroup['name'], (new CUrl('zabbix.php'))
				->setArgument('action', 'usergroup.edit')
				->setArgument('usrgrpid', $usergroup['usrgrpid'])
				->getUrl()
			))
				->addClass($usergroup['gui_access'] == GROUP_GUI_ACCESS_DISABLED
					|| $usergroup['users_status'] == GROUP_STATUS_DISABLED
					? ZBX_STYLE_LINK_ALT . ' ' . ZBX_STYLE_RED
					: ZBX_STYLE_LINK_ALT . ' ' . ZBX_STYLE_GREEN);
		}
	}

	$name = new CLink($adusrgrp['name'], (new CUrl('zabbix.php'))
		->setArgument('action', 'adusergrps.edit')
		->setArgument('adusrgrpid', $adGroupId)
		->getUrl()
	);

	// Append LDAP group to table
	$adGroupTable->addRow([
		new CCheckBox('adusrgrpid['.$adGroupId.']', $adGroupId),
		(new CCol($name))->addClass(ZBX_STYLE_NOWRAP),
		$userGroups,
		$adusrgrp['role']
	]);
}

// append table to form
$adGroupsForm->addItem([
	$adGroupTable,
	$this->data['paging'],
	new CActionButtonList('action', 'adusrgrpid', [
		'adusergrps.delete' => ['name' => _('Delete'), 'confirm' => _('Delete selected LDAP groups?')]
	], 'adusergroup')
]);

// append form to widget
$widget->addItem($adGroupsForm);
$widget->show();
