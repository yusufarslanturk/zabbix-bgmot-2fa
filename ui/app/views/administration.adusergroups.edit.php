<?php

$this->includeJsFile('administration.adusergroups.edit.js.php');
$this->addJsFile('multiselect.js');

$widget = (new CWidget())->setTitle(_('LDAP groups'));

// create form
$adGroupForm = (new CForm())
	->setId('ad-group-form')
	->setName('ad_group_form')
	->setAttribute('aria-labeledby', ZBX_STYLE_PAGE_TITLE)
	->addVar('action', $data['action']);

if ($data['adusrgrpid'] != 0) {
	$adGroupForm->addVar('adusrgrpid', $data['adusrgrpid']);
}

$adGroupFormList = (new CFormList())
	->addRow(
		(new CLabel(_('LDAP group name'), 'adgname'))->setAsteriskMark(),
		(new CTextBox('adgname', $data['name']))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired()
			->setAttribute('autofocus', 'autofocus')
			->setAttribute('maxlength', DB::getFieldLength('adusrgrp', 'name'))
	);

$user_groups = [];
foreach ($data['groups'] as $group) {
	$user_groups[] = CArrayHelper::renameKeys($group, ['usrgrpid' => 'id']);
}
$adGroupFormList->addRow(
		(new CLabel(_('User groups'), 'user_groups__ms'))->setAsteriskMark(),
		(new CMultiSelect([
			'name' => 'user_groups[]',
			'object_name' => 'usersGroups',
			'data' => $user_groups,
			'popup' => [
				'parameters' => [
					'srctbl' => 'usrgrp',
					'srcfld1' => 'usrgrpid',
					'dstfrm' => $adGroupForm->getName(),
					'dstfld1' => 'user_groups_'
				]
			]
		]))
		->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired()
	);

$role_multiselect = (new CMultiSelect([
	'name' => 'roleid',
	'object_name' => 'roles',
	'data' => $data['role'],
	'multiple' => false,
	'disabled' => false,
	'popup' => [
		'parameters' => [
			'srctbl' => 'roles',
			'srcfld1' => 'roleid',
			'dstfrm' => 'user_form',
			'dstfld1' => 'roleid'
		]
	]
]))->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);
$adGroupFormList->addRow((new CLabel(_('Role')))->setAsteriskMark(), $role_multiselect);

if ($data['roleid']) {
	$adGroupFormList->addRow(_('User type'),
		new CTextBox('user_type', user_type2str($data['user_type']), true)
	);

	$permissions_table = (new CTable())
		->setAttribute('style', 'width: 100%;')
		->setHeader([_('Host group'), _('Permissions')]);

	if ($data['user_type'] == USER_TYPE_SUPER_ADMIN) {
		$permissions_table->addRow([italic(_('All groups')), permissionText(PERM_READ_WRITE)]);
	}
	else {
		foreach ($data['groups_rights'] as $groupid => $group_rights) {
			if (array_key_exists('grouped', $group_rights) && $group_rights['grouped']) {
				$group_name = ($groupid == 0)
					? italic(_('All groups'))
					: [$group_rights['name'], SPACE, italic('('._('including subgroups').')')];
			}
			else {
				$group_name = $group_rights['name'];
			}
			$permissions_table->addRow([$group_name, permissionText($group_rights['permission'])]);
		}
	}

	$adGroupFormList
		->addRow(_('Permissions'),
			(new CDiv($permissions_table))
				->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
				->setAttribute('style', 'min-width: '.ZBX_TEXTAREA_BIG_WIDTH.'px;')
		)
		->addInfo(_('Permissions can be assigned for user groups only.'));

	$adGroupFormList
		->addRow((new CTag('h4', true, _('Access to UI elements')))->addClass('input-section-header'));

	foreach (CRoleHelper::getUiSectionsLabels($data['user_type']) as $section_name => $section_label) {
		$elements = [];

		foreach (CRoleHelper::getUiSectionRulesLabels($section_name, $data['user_type']) as $rule_name => $rule_label) {
			$elements[] = (new CSpan($rule_label))->addClass(
				CRoleHelper::checkAccess($rule_name, $data['roleid']) ? ZBX_STYLE_STATUS_GREEN : ZBX_STYLE_STATUS_GREY
			);
		}

		if ($elements) {
			$adGroupFormList->addRow($section_label, (new CDiv($elements))
				->setWidth(ZBX_TEXTAREA_BIG_WIDTH)
				->addClass('rules-status-container')
			);
		}
	}

	$adGroupFormList->addRow((new CTag('h4', true, _('Access to modules')))->addClass('input-section-header'));

	if (!$data['modules']) {
		$adGroupFormList->addRow(italic(_('No enabled modules found.')));
	}
	else {
		$elements = [];

		foreach ($data['modules'] as $moduleid => $module) {
			$elements[] = (new CSpan($module['id']))->addClass(
				CRoleHelper::checkAccess(CRoleHelper::MODULES_MODULE.$moduleid, $data['roleid'])
					? ZBX_STYLE_STATUS_GREEN
					: ZBX_STYLE_STATUS_GREY
			);
		}

		if ($elements) {
			$adGroupFormList->addRow((new CDiv($elements))
				->setWidth(ZBX_TEXTAREA_BIG_WIDTH)
				->addClass('rules-status-container')
			);
		}
	}

	$api_access_enabled = CRoleHelper::checkAccess(CRoleHelper::API_ACCESS, $data['roleid']);
	$adGroupFormList
		->addRow((new CTag('h4', true, _('Access to API')))->addClass('input-section-header'))
		->addRow((new CDiv((new CSpan($api_access_enabled ? _('Enabled') : _('Disabled')))->addClass(
				$api_access_enabled ? ZBX_STYLE_STATUS_GREEN : ZBX_STYLE_STATUS_GREY
			)))
			->setWidth(ZBX_TEXTAREA_BIG_WIDTH)
			->addClass('rules-status-container')
		);

	$api_methods = CRoleHelper::getRoleApiMethods($data['roleid']);

	if ($api_methods) {
		$api_access_mode_allowed = CRoleHelper::checkAccess(CRoleHelper::API_MODE, $data['roleid']);
		$elements = [];

		foreach ($api_methods as $api_method) {
			$elements[] = (new CSpan($api_method))->addClass(
				$api_access_mode_allowed ? ZBX_STYLE_STATUS_GREEN : ZBX_STYLE_STATUS_GREY
			);
		}

		$adGroupFormList->addRow($api_access_mode_allowed ? _('Allowed methods') : _('Denied methods'),
			(new CDiv($elements))
				->setWidth(ZBX_TEXTAREA_BIG_WIDTH)
				->addClass('rules-status-container')
		);
	}

	$adGroupFormList->addRow((new CTag('h4', true, _('Access to actions')))->addClass('input-section-header'));
	$elements = [];

	foreach (CRoleHelper::getActionsLabels($data['user_type']) as $rule_name => $rule_label) {
		$elements[] = (new CSpan($rule_label))
			->addClass(CRoleHelper::checkAccess($rule_name, $data['roleid'])
				? ZBX_STYLE_STATUS_GREEN
				: ZBX_STYLE_STATUS_GREY
			);
	}

	$adGroupFormList->addRow((new CDiv($elements))
		->setWidth(ZBX_TEXTAREA_BIG_WIDTH)
		->addClass('rules-status-container')
	);
}

// append form lists to tab
$adGroupTab = (new CTabView())
	->addTab('adGroupTab', _('LDAP group'), $adGroupFormList);
if (!$data['form_refresh']) {
	$adGroupTab->setSelected(0);
}

$cancel_button = (new CRedirectButton(_('Cancel'), (new CUrl('zabbix.php'))
	->setArgument('action', 'adusergrps.list')
	->setArgument('page', CPagerHelper::loadPage('adusergrps.list', null))
))->setId('cancel');

// append buttons to form
if ($data['adusrgrpid'] != 0) {
	$adGroupTab->setFooter(makeFormFooter(
		(new CSubmitButton(_('Update'), 'action', 'adusergrps.update' ))->setId('update'),
		[
			(new CRedirectButton(_('Delete'),
				(new CUrl('zabbix.php'))->setArgument('action', 'adusergrps.delete')
					->setArgument('adusrgrpid', [$data['adusrgrpid']])->setArgumentSID(),
				_('Delete selected LDAP group?')
			))->setId('delete'),
			$cancel_button
		]
	));
}
else {
	$adGroupTab->setFooter(makeFormFooter(
		(new CSubmitButton(_('Add'), 'action', 'adusergrps.create'))->setId('add'),
		[
			$cancel_button
		]
	));
}

// append tab to form
$adGroupForm->addItem($adGroupTab);

$widget->addItem($adGroupForm);

$widget->show();
