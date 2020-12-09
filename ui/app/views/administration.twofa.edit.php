<?php
/*
** Zabbix
** Copyright (C) 2001-2017 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

$this->includeJsFile('administration.twofa.edit.js.php');

$widget = (new CWidget())->setTitle(_('Two factor authentication'));

// create form
$twofaForm = (new CForm())->setName('twofaForm');

// create form list
$twofaFormList = new CFormList('twofaList');

// append 2fa_type radio buttons to form list
$twofaFormList->addRow(_('Two factor authentication'),
	(new CRadioButtonList('2fa_type', (int) $data['2fa_type']))
		->addValue(_('None'), ZBX_AUTH_2FA_NONE, null, 'submit()')
		->addValue(_('DUO'), ZBX_AUTH_2FA_DUO, null, 'submit()')
		->setModern(true)
);

// Add current value of 2fa_type
$twofaForm->addVar('action', $data['action_submit']);

// append DUO fields to form list
if ($data['2fa_type'] == ZBX_AUTH_2FA_DUO) {
	$twofaFormList->addRow(
		_('API hostname'),
		(new CTextBox('2fa_duo_api_hostname', $data['2fa_duo_api_hostname']))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	);
	$twofaFormList->addRow(
		_('Integration key'),
		(new CTextBox('2fa_duo_integration_key', $data['2fa_duo_integration_key']))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	);
	$twofaFormList->addRow(
		_('Secret key'),
		(new CPassBox('2fa_duo_secret_key', $data['2fa_duo_secret_key']))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	);
	$twofaFormList->addRow(
		_('40 characters long custom key'),
		(new CPassBox('2fa_duo_a_key', $data['2fa_duo_a_key'], 40))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	);
}

// append form list to tab
$twofaTab = new CTabView();
$twofaTab->addTab('twofaTab', '2FA', $twofaFormList);

// create save button
$saveButton = new CSubmit('update', _('Update'));

$twofaTab->setFooter(makeFormFooter($saveButton));

// append tab to form
$twofaForm->addItem($twofaTab);

// append form to widget
$widget->addItem($twofaForm);

$widget->show();
