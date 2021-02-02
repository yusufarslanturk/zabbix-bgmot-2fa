<?php

$this->addJsFile('multiselect.js');

$widget = (new CWidget())->setTitle(_('LDAP groups'));

// create form
$adGroupForm = (new CForm())
	->setName('ad_group_form')
	->setAttribute('aria-labeledby', ZBX_STYLE_PAGE_TITLE);

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

$type_select = (new CSelect('user_type'))
	->setFocusableElementId('label-type')
	->setValue($data['user_type'])
	->addOptions(CSelect::createOptionsFromArray(user_type2str()));
$adGroupFormList->addRow(new CLabel(_('User type for users in this LDAP group'), $type_select->getFocusableElementId()), $type_select);

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
