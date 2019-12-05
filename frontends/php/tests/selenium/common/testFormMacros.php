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
require_once 'vendor/autoload.php';

require_once dirname(__FILE__).'/../../include/CWebTest.php';
require_once dirname(__FILE__).'/../traits/MacrosTrait.php';

/**
 * Base class for Macros tests.
 */
abstract class testFormMacros extends CWebTest {

	use MacrosTrait;

	const SQL_HOSTS = 'SELECT * FROM hosts ORDER BY hostid';

	public static function getHash() {
		return CDBHelper::getHash(self::SQL_HOSTS);
	}

	public static function getCreateCommonMacrosData() {
		return [
			[
				[
					'expected' => TEST_GOOD,
					'Name' => 'With Macros',
					'macros' => [
						[
							'action' => USER_ACTION_UPDATE,
							'index' => 0,
							'Macro' => '{$1234}',
							'Value' => '!@#$%^&*()_+/*',
							'Description' => '!@#$%^&*()_+/*',
						],
						[
							'Macro' => '{$MACRO1}',
							'Value' => 'Value_1',
							'Description' => 'Test macro Description 1'
						],
						[
							'Macro' => '{$MACRO3}',
							'Value' => '',
							'Description' => '',
						],
						[
							'Macro' => '{$MACRO4}',
							'Value' => 'Value',
							'Description' => '',
						],
						[
							'Macro' => '{$MACRO5}',
							'Value' => '',
							'Description' => 'DESCRIPTION',
						],
						[
							'Macro' => '{$MACRO6}',
							'Value' => 'Значение',
							'Description' => 'Описание',
						],
						[
							'Macro' => '{$MACRO:A}',
							'Value' => '{$MACRO:A}',
							'Description' => '{$MACRO:A}',
						]
					]
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'Name' => 'Without dollar in Macros',
					'macros' => [
						[
							'action' => USER_ACTION_UPDATE,
							'index' => 0,
							'Macro' => '{MACRO}',
						]
					],
					'error_details' => 'Invalid macro "{MACRO}": incorrect syntax near "MACRO}".'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'Name' => 'With empty Macro',
					'macros' => [
						[
							'action' => USER_ACTION_UPDATE,
							'index' => 0,
							'Macro' => '',
							'Value' => 'Macro_Value',
							'Description' => 'Macro Description'
						]
					],
					'error_details' => 'Invalid macro "": macro is empty.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'Name' => 'With repeated Macros',
					'macros' => [
						[
							'action' => USER_ACTION_UPDATE,
							'index' => 0,
							'Macro' => '{$MACRO}',
							'Value' => 'Macro_Value_1',
							'Description' => 'Macro Description_1'
						],
						[
							'Macro' => '{$MACRO}',
							'Value' => 'Macro_Value_2',
							'Description' => 'Macro Description_2'
						]
					],
					'error_details' => 'Macro "{$MACRO}" is not unique.'
				]
			]
		];
	}

	/**
	 * Test creating of host or template with Macros.
	 */
	protected function checkCreate($host_type, $data) {
		$this->page->login()->open($host_type.'s.php?form=create');
		$form = $this->query('name:'.$host_type.'sForm')->waitUntilPresent()->asForm()->one();

		$form->fill([
			ucfirst($host_type).' name' => $data['Name'],
			'Groups' => 'Zabbix servers'
		]);

		$this->checkMacros(' added', $data['Name'], $host_type, $data, 'Cannot add ');
	}

	public static function getUpdateCommonMacrosData() {
		return [
			[
				[
					'expected' => TEST_GOOD,
					'macros' => [
						[
							'action' => USER_ACTION_UPDATE,
							'index' => 0,
							'Macro' => '{$UPDATED_MACRO1}',
							'Value' => 'updated value1',
							'Description' => 'updated description 1',
						],
						[
							'action' => USER_ACTION_UPDATE,
							'index' => 1,
							'Macro' => '{$UPDATED_MACRO2}',
							'Value' => 'Updated value 2',
							'Description' => 'Updated description 2',
						]
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'macros' => [
						[
							'action' => USER_ACTION_UPDATE,
							'index' => 0,
							'Macro' => '{$UPDATED_MACRO1}',
							'Value' => '',
							'Description' => '',
						],
						[
							'action' => USER_ACTION_UPDATE,
							'index' => 1,
							'Macro' => '{$UPDATED_MACRO2}',
							'Value' => 'Updated Value 2',
							'Description' => '',
						],
						[
							'Macro' => '{$UPDATED_MACRO3}',
							'Value' => '',
							'Description' => 'Updated Description 3',
						]
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'macros' => [
						[
							'action' => USER_ACTION_UPDATE,
							'index' => 0,
							'Macro' => '{$MACRO:A}',
							'Value' => '{$MACRO:B}',
							'Description' => '{$MACRO:C}',
						],
						[
							'action' => USER_ACTION_UPDATE,
							'index' => 1,
							'Macro' => '{$UPDATED_MACRO_1}',
							'Value' => '',
							'Description' => 'DESCRIPTION',
						],
						[
							'action' => USER_ACTION_UPDATE,
							'index' => 2,
							'Macro' => '{$UPDATED_MACRO_2}',
							'Value' => 'Значение',
							'Description' => 'Описание',
						]
					]
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'Name' => 'Without dollar in Macros',
					'macros' => [
						[
							'action' => USER_ACTION_UPDATE,
							'index' => 0,
							'Macro' => '{MACRO}',
						]
					],
					'error_details' => 'Invalid macro "{MACRO}": incorrect syntax near "MACRO}".'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'Name' => 'With empty Macro',
					'macros' => [
						[
							'action' => USER_ACTION_UPDATE,
							'index' => 0,
							'Macro' => '',
							'Value' => 'Macro_Value',
							'Description' => 'Macro Description'
						]
					],
					'error_details' => 'Invalid macro "": macro is empty.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'Name' => 'With repeated Macros',
					'macros' => [
						[
							'action' => USER_ACTION_UPDATE,
							'index' => 0,
							'Macro' => '{$MACRO}',
							'Value' => 'Macro_Value_1',
							'Description' => 'Macro Description_1'
						],
						[
							'action' => USER_ACTION_UPDATE,
							'index' => 1,
							'Macro' => '{$MACRO}',
							'Value' => 'Macro_Value_2',
							'Description' => 'Macro Description_2'
						]
					],
					'error_details' => 'Macro "{$MACRO}" is not unique.'
				]
			]
		];
	}

	/**
	 * Test updating of host or template with Macros.
	 */
	protected function checkUpdate($host_type, $data, $hostname) {
		$id = CDBHelper::getValue('SELECT hostid FROM hosts WHERE host='.zbx_dbstr($hostname));

		$this->page->login()->open($host_type.'s.php?form=update&'.$host_type.'id='.$id.'&groupid=0');
		$this->checkMacros(' updated', $hostname, $host_type, $data, 'Cannot update ');
	}

	/**
	 * Test removing Macros from host or template.
	 */
	protected function checkRemove($host_type, $hostname) {
		$id = CDBHelper::getValue('SELECT hostid FROM hosts WHERE host='.zbx_dbstr($hostname));

		$this->page->login()->open($host_type.'s.php?form=update&'.$host_type.'id='.$id.'&groupid=0');
		$form = $this->query('name:'.$host_type.'sForm')->waitUntilPresent()->asForm()->one();
		$form->selectTab('Macros');
		$this->removeMacros();
		$form->submit();

		$message = CMessageElement::find()->one();
		$this->assertTrue($message->isGood());
		$this->assertEquals(ucfirst($host_type).' updated', $message->getTitle());
		$this->assertEquals(1, CDBHelper::getCount('SELECT NULL FROM hosts WHERE host='.zbx_dbstr($hostname)));
		// Check the results in form.
		$this->checkMacrosFields($host_type, null, $hostname);
	}

	/**
	 * Check adding and saving macros in host or template form.
	 */
	private function checkMacros($action, $name, $host_type, $data, $error_message) {
		if ($data['expected'] === TEST_BAD) {
			$old_hash = $this->getHash();
		}

		$form = $this->query('name:'.$host_type.'sForm')->waitUntilPresent()->asForm()->one();
		$form->selectTab('Macros');
		$this->fillMacros($data['macros']);
		$form->submit();

		$message = CMessageElement::find()->one();
		switch ($data['expected']) {
			case TEST_GOOD:
				$this->assertTrue($message->isGood());
				$this->assertEquals(ucfirst($host_type).$action, $message->getTitle());
				$this->assertEquals(1, CDBHelper::getCount('SELECT NULL FROM hosts WHERE host='.zbx_dbstr($name)));
				// Check the results in form.
				$this->checkMacrosFields($host_type, $data, $name);
				break;
			case TEST_BAD:
				$this->assertTrue($message->isBad());
				$this->assertEquals($error_message.$host_type, $message->getTitle());
				$this->assertTrue($message->hasLine($data['error_details']));
				// Check that DB hash is not changed.
				$this->assertEquals($old_hash, CDBHelper::getHash(self::SQL_HOSTS));
				break;
		}
	}

	/**
	 * Checking saved macros in host or template form.
	 */
	private function checkMacrosFields($host_type, $data = null, $name) {
		$id = CDBHelper::getValue('SELECT hostid FROM hosts WHERE host='.zbx_dbstr($name));

		$this->page->open($host_type.'s.php?form=update&'.$host_type.'id='.$id.'&groupid=0');
		$form = $this->query('id:'.$host_type.'sForm')->waitUntilPresent()->asForm()->one();
		$form->selectTab('Macros');

		if ($data !== null) {
			$this->assertMacros($data['macros']);
		}
		else {
			$this->assertMacros();
		}
	}
}
