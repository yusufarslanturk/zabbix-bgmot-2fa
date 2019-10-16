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

require_once dirname(__FILE__) . '/../include/CWebTest.php';
require_once dirname(__FILE__).'/traits/MacrosTrait.php';

/**
 * @backup hosts
 */
class testFormHostMacros extends CWebTest {

	use MacrosTrait;

	public static function getCreateData() {
		return [
			[
				[
					'expected' => TEST_GOOD,
					'Host name' => 'Host With Macros',
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
					'Host name' => 'Host without dollar in Macros',
					'macros' => [
						[
							'action' => USER_ACTION_UPDATE,
							'index' => 0,
							'Macro' => '{MACRO}',
						]
					],
					'error' => 'Cannot add host',
					'error_details' => 'Invalid macro "{MACRO}": incorrect syntax near "MACRO}".'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'Host name' => 'Host without dollar in Macros',
					'macros' => [
						[
							'action' => USER_ACTION_UPDATE,
							'index' => 0,
							'Macro' => '{MACRO}',
						]
					],
					'error' => 'Cannot add host',
					'error_details' => 'Invalid macro "{MACRO}": incorrect syntax near "MACRO}".'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'Host name' => 'Host with empty Macro',
					'macros' => [
						[
							'action' => USER_ACTION_UPDATE,
							'index' => 0,
							'Macro' => '',
							'Value' => 'Macro_Value',
							'Description' => 'Macro Description'
						]
					],
					'error' => 'Cannot add host',
					'error_details' => 'Invalid macro "": macro is empty.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'Host name' => 'Host with repeated Macros',
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
					'error' => 'Cannot add host',
					'error_details' => 'Macro "{$MACRO}" is not unique.'
				]
			]
		];
	}

	/**
	 * Test creating of host with Macros.
	 *
	 * @dataProvider getCreateData
	 */
	public function testFormHostMacros_Create($data) {
		$sql_hosts = "SELECT * FROM hosts ORDER BY hostid";
		$old_hash = CDBHelper::getHash($sql_hosts);

		$this->page->login()->open('hosts.php?form=create');
		$form = $this->query('name:hostsForm')->waitUntilPresent()->asForm()->one();

		$form->fill([
			'Host name' => $data['Host name'],
			'Groups' => 'Zabbix servers'
		]);

		$form->selectTab('Macros');
		$this->fillMacros($data['macros']);
		$form->submit();

		$message = CMessageElement::find()->one();
		switch ($data['expected']) {
			case TEST_GOOD:
				$this->assertTrue($message->isGood());
				$this->assertEquals('Host added', $message->getTitle());
				$this->assertEquals(1, CDBHelper::getCount('SELECT NULL FROM hosts WHERE host='.zbx_dbstr($data['Host name'])));
				// Check the results in form.
				$this->checkMacrosFields($data);
				break;
			case TEST_BAD:
				$this->assertTrue($message->isBad());
				$this->assertEquals($data['error'], $message->getTitle());
				$this->assertTrue($message->hasLine($data['error_details']));
				// Check that DB hash is not changed.
				$this->assertEquals($old_hash, CDBHelper::getHash($sql_hosts));
				break;
		}
	}

	private function checkMacrosFields($data) {
		$id = CDBHelper::getValue('SELECT hostid FROM hosts WHERE host='.zbx_dbstr($data['Host name']));
		$this->page->open('hosts.php?form=update&hostid='.$id.'&groupid=0');
		$form = $this->query('id:hostsForm')->waitUntilPresent()->asForm()->one();
		$form->selectTab('Macros');
		$this->assertMacros($data['macros']);
	}
}

