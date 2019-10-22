<?php
/*
** Zabbix
** Copyright (C) 2001-2019 Zabbix SIA
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

require_once dirname(__FILE__).'/common/testFormMacros.php';

/**
 * @backup hosts
 */
class testFormTemplateMacros extends testFormMacros {

	use MacrosTrait;

	/**
	* The id of the template for updating macros.
	*
	* @var string
	*/
	protected $templateid_update = '40000';

	/**
	* The name of the template for updating macros.
	*
	* @var string
	*/
	protected $template_name_update = 'Form test template';

	/**
	* The id of the template for removing macros.
	*
	* @var string
	*/
	protected $templateid_remove = '99016';

	/**
	* The name of the template for removing macros.
	*
	* @var string
	*/
	protected $template_name_remove = 'Template to test graphs';

	/**
	 * @dataProvider getCreateCommonMacrosData
	 */
	public function testFormTemplateMacros_Create($data) {
		$this->checkCreate('template', $data);
	}

	/**
	 * @dataProvider getUpdateCommonMacrosData
	 */
	public function testFormTemplateMacros_Update($data) {
		$this->checkUpdate('template', $data, $this->templateid_update, $this->template_name_update);
	}

	public function testFormHTemplateMacros_Remove() {
		$this->checkRemove('template', $this->templateid_remove, $this->template_name_remove);
	}
}
