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
	 * Test changing or resetting global macro on host or template.
	 */
	protected function checkChangeRemoveInheritedMacro($host_type) {
		$host = [
			ucfirst($host_type).' name' => 'With edited global macro',
			'Groups' => 'Zabbix servers'
		];

		$this->page->login()->open($host_type.'s.php?form=create');
		$form = $this->query('name:'.$host_type.'sForm')->waitUntilPresent()->asForm()->one();
		$form->fill($host);

		$form->selectTab('Macros');

		// Check inherited macros before editing.
		$this->checkInheritedMacrosFrontendAndDB($host_type);

		$edited_macros = [
			[
				'Macro' => '{$1}',
				'Value' => 'New updated Numeric macro 1',
				'Description' => 'New updated Test description 2'
			]
		];

		$count = count($edited_macros);
		// Change macro.
		for ($i = 0; $i < $count; $i += 1) {
			$this->query('id:macros_'.$i.'_change')->one()->click();
			$this->query('id:macros_'.$i.'_value')->one()->fill($edited_macros[$i]['Value']);
			$this->query('id:macros_'.$i.'_description')->one()->fill($edited_macros[$i]['Description']);
		}

		$form->submit();

		// Check saved edited macros in host/template form.
		$id = CDBHelper::getValue('SELECT hostid FROM hosts WHERE host='.zbx_dbstr($host[ucfirst($host_type).' name']));
		$this->page->login()->open($host_type.'s.php?form=update&'.$host_type.'id='.$id.'&groupid=0');
		$form->selectTab('Macros');
		$this->assertMacros($edited_macros);

		// Remove edited macro and reset to global.
		$this->query('id:show_inherited_macros')->waitUntilPresent()
			->asSegmentedRadio()->one()->fill('Inherited and '.$host_type.' macros');

		for ($i = 0; $i < $count; $i += 1) {
			$this->query('id:macros_'.$i.'_change')->one()->click();
		}

		$form->submit();

		$this->page->login()->open($host_type.'s.php?form=update&'.$host_type.'id='.$id.'&groupid=0');
		$form->selectTab('Macros');

		$edited_macros = [];
		$this->assertMacros($edited_macros);

		// Check inherited macros again after remove.
		$this->checkInheritedMacrosFrontendAndDB($host_type);
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

	private function checkInheritedMacrosFrontendAndDB($host_type) {
		$this->query('id:show_inherited_macros')->waitUntilPresent()
			->asSegmentedRadio()->one()->fill('Inherited and '.$host_type.' macros');
		// Create two macros arrays: from DB and from Frontend form.
		$macros = [
			'database' => CDBHelper::getAll('SELECT macro, value, description FROM globalmacro'),
			'frontend' => []
		];

		// Write macros rows from Frontend to array.
		$table = $this->query('id:tbl_macros')->asTable()->one();
		$count = $table->getRows()->count() - 1;
		for ($i = 0; $i < $count; $i += 2) {
			$macro = [];
			$row = $table->getRow($i);
			$macro['macro'] = $row->query('xpath:./td[1]/textarea')->one()->getValue();
			$macro['value'] = $row->query('xpath:./td[3]/textarea')->one()->getValue();
			$macro['description'] = $table->getRow($i + 1)->query('tag:textarea')->one()->getValue();

			$macros['frontend'][] = $macro;
		}

		// Sort arrays by Macros.
		foreach ($macros as &$array) {
			usort($array, function ($a, $b) {
				return strcmp($a['macro'], $b['macro']);
			});
		}
		unset($array);

		// Compare macros from DB with macros from Frontend.
		$this->assertEquals($macros['database'], $macros['frontend']);
	}
}
