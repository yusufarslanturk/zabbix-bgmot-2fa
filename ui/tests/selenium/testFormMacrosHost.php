<?php
/*
** Zabbix
** Copyright (C) 2001-2022 Zabbix SIA
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
class testFormMacrosHost extends testFormMacros {

	use MacrosTrait;

	const RESOLVE_MACRO = '{$X_SECRET_HOST_MACRO_2_RESOLVE}';

	/**
	 * The name of the host for updating macros, id=20006.
	 *
	 * @var string
	 */
	protected $host_name_update = 'Host for trigger description macros';

	/**
	 * The name of the host for removing macros, id=30010.
	 *
	 * @var string
	 */
	protected $host_name_remove = 'Host for macros remove';

	/**
	 * The id of the host for removing inherited macros.
	 *
	 * @var integer
	 */
	protected static $hostid_remove_inherited;

	/**
	 * @dataProvider getCreateMacrosData
	 */
	public function testFormMacrosHost_Create($data) {
		$this->checkMacros($data, 'host');
	}

	/**
	 * @dataProvider getUpdateMacrosData
	 */
	public function testFormMacrosHost_Update($data) {
		$this->checkMacros($data, 'host', $this->host_name_update, true);
	}

	public function testFormMacrosHost_RemoveAll() {
		$this->checkRemoveAll($this->host_name_remove, 'host');
	}

	/**
	 * @dataProvider getCheckInheritedMacrosData
	 */
	public function testFormMacrosHost_ChangeInheritedMacro($data) {
		$this->checkChangeInheritedMacros($data, 'host');
	}

	public function prepareHostRemoveMacrosData() {
		$response = CDataHelper::call('host.create', [
				'host' => 'Host for Inherited macros removing',
				'groups' => [
					['groupid' => '4']
				],
				'interfaces' => [
					'type'=> 1,
					'main' => 1,
					'useip' => 1,
					'ip' => '192.168.3.1',
					'dns' => '',
					'port' => '10050'
				],
				'macros' => [
					[
						'macro' => '{$TEST_MACRO123}',
						'value' => 'test123',
						'description' => 'description 123'
					],
					[
						'macro' => '{$MACRO_FOR_DELETE_HOST1}',
						'value' => 'test1',
						'description' => 'description 1'
					],
					[
						'macro' => '{$MACRO_FOR_DELETE_HOST2}',
						'value' => 'test2',
						'description' => 'description 2'
					],
					[
						'macro' => '{$MACRO_FOR_DELETE_GLOBAL1}',
						'value' => 'test global 1',
						'description' => 'global description 1'
					],
					[
						'macro' => '{$MACRO_FOR_DELETE_GLOBAL2}',
						'value' => 'test global 2',
						'description' => 'global description 2'
					],
					[
						'macro' => '{$SNMP_COMMUNITY}',
						'value' => 'redefined value',
						'description' => 'redefined description'
					]
				]
		]);
		$this->assertArrayHasKey('hostids', $response);
		self::$hostid_remove_inherited = $response['hostids'][0];
	}

	/**
	 * @dataProvider getRemoveInheritedMacrosData
	 *
	 * @onBeforeOnce prepareHostRemoveMacrosData
	 */
	public function testFormMacrosHost_RemoveInheritedMacro($data) {
		$this->checkRemoveInheritedMacros($data, 'host', self::$hostid_remove_inherited, false, null,
				'Host for Inherited macros removing'
		);
	}

	public function getSecretMacrosLayoutData() {
		return [
			[
				[
					'macro' => '{$SECRET_HOST_MACRO}',
					'type' => 'Secret text'
				]
			],
			[
				[
					'macro' => '{$SECRET_HOST_MACRO}',
					'type' => 'Secret text',
					'chenge_type' => true
				]
			],
			[
				[
					'macro' => '{$TEXT_HOST_MACRO}',
					'type' => 'Text'
				]
			],
			[
				[
					'global' => true,
					'macro' => '{$X_TEXT_2_SECRET}',
					'type' => 'Text'
				]
			],
			[
				[
					'global' => true,
					'macro' => '{$X_SECRET_2_SECRET}',
					'type' => 'Secret text'
				]
			]
		];
	}

	/**
	 * @dataProvider getSecretMacrosLayoutData
	 */
	public function testFormMacrosHost_CheckSecretMacrosLayout($data) {
		$this->checkSecretMacrosLayout($data, 'zabbix.php?action=host.view', 'hosts', 'Host for suppression');
	}

	public function getCreateSecretMacrosData() {
		return [
			[
				[
					'macro_fields' => [
						'action' => USER_ACTION_UPDATE,
						'index' => 0,
						'macro' => '{$SECRET_MACRO}',
						'value' => [
							'text' => 'host secret value',
							'type' => 'Secret text'
						],
						'description' => 'secret description'
					],
					'check_default_type' => true
				]
			],
			[
				[
					'macro_fields' => [
						'macro' => '{$TEXT_MACRO}',
						'value' => [
							'text' => 'host plain text value',
							'type' => 'Secret text'
						],
						'description' => 'plain text description'
					],
					'back_to_text' => true
				]
			],
			[
				[
					'macro_fields' => [
						'macro' => '{$SECRET_EMPTY_MACRO}',
						'value' => [
							'text' => '',
							'type' => 'Secret text'
						],
						'description' => 'secret empty value'
					]
				]
			]
		];
	}

	/**
	 * @dataProvider getCreateSecretMacrosData
	 */
	public function testFormMacrosHost_CreateSecretMacros($data) {
		$this->createSecretMacros($data, 'zabbix.php?action=host.view', 'hosts', 'Available host');
	}

	public function getRevertSecretMacrosData() {
		return [
			[
				[
					'macro_fields' => [
						'macro' => '{$SECRET_HOST_MACRO_REVERT}',
						'value' => 'Secret host value'
					]
				]
			],
			[
				[
					'macro_fields' => [
						'macro' => '{$SECRET_HOST_MACRO_2_TEXT_REVERT}',
						'value' => 'Secret host value 2'
					],
					'set_to_text' => true
				]
			]
		];
	}

	/**
	 * @dataProvider getRevertSecretMacrosData
	 */
	public function testFormMacrosHost_RevertSecretMacroChanges($data) {
		$this->revertSecretMacroChanges($data, 'zabbix.php?action=host.view', 'hosts', 'Available host in maintenance');
	}

	public function getUpdateSecretMacrosData() {
		return [
			[
				[
					'action' => USER_ACTION_UPDATE,
					'index' => 2,
					'macro' => '{$SECRET_HOST_MACRO_UPDATE}',
					'value' => [
						'text' => 'Updated secret value'
					]
				]
			],
			[
				[
					'action' => USER_ACTION_UPDATE,
					'index' => 3,
					'macro' => '{$SECRET_HOST_MACRO_UPDATE_2_TEXT}',
					'value' => [
						'text' => 'New text value',
						'type' => 'Text'
					]
				]
			],
			[
				[
					'action' => USER_ACTION_UPDATE,
					'index' => 4,
					'macro' => '{$TEXT_HOST_MACRO_2_SECRET}',
					'value' => [
						'text' => 'New secret value',
						'type' => 'Secret text'
					]
				]
			]
		];
	}

	/**
	 * @dataProvider getUpdateSecretMacrosData
	 */
	public function testFormMacrosHost_UpdateSecretMacros($data) {
		$this->updateSecretMacros($data, 'zabbix.php?action=host.view', 'hosts', 'Available host in maintenance');
	}

	public function getResolveSecretMacroData() {
		return [
			// Latest data page. Macro is resolved only in key.
			[
				[
					'url' => 'zabbix.php?action=latest.view&hostids%5B%5D=99135&show_details=1',
					'name' => 'Macro value: '.self::RESOLVE_MACRO,
					'key' => 'trap[Value 2 B resolved]',
					'key_secret' => 'trap[******]'
				]
			],
			// Hosts items page. Macro is not resolved in any field.
			[
				[
					'url' => 'items.php?filter_set=1&filter_hostids%5B0%5D=99135&context=host',
					'name' => 'Macro value: '.self::RESOLVE_MACRO,
					'key' => 'trap['.self::RESOLVE_MACRO.']',
					'key_secret' => 'trap['.self::RESOLVE_MACRO.']'
				]
			]
		];
	}

	/**
	 * Test opens the list of items of "Available host in maintenance" and "Latest data"
	 * and checks macro resolution in item fields.
	 *
	 * @dataProvider getResolveSecretMacroData
	 */
	public function testFormMacrosHost_ResolveSecretMacro($data) {
		$this->checkItemFieds($data['url'], $data['name'], $data['key']);

		// Open host form in popup and change macro type to secret.
		$form = $this->openMacrosTab('zabbix.php?action=host.view', 'hosts', false, 'Available host in maintenance');
		$this->getValueField(self::RESOLVE_MACRO)->changeInputType(CInputGroupElement::TYPE_SECRET);

		$form->submit();
		$this->page->waitUntilReady();
		$this->assertMessage(TEST_GOOD, 'Host updated');

		$this->checkItemFieds($data['url'], $data['name'], $data['key_secret']);
	}

	/**
	 * 	Function for checking item field on Latest data or Items page.
	 *
	 * @param string $url    Latest data or Items page URL
	 * @param string $name   item name
	 * @param string $key    item key
	 */
	private function checkItemFieds($url, $name, $key) {
		$this->page->login()->open($url)->waitUntilReady();
		$table = $this->query('xpath://form[@name="items"]/table[@class="list-table"] |'.
				' //table[contains(@class, "overflow-ellipsis")]')->asTable()->waitUntilPresent()->one();

		$name_column = $table->findRow('Name', $name, true)->getColumn('Name');
		$this->assertEquals($name, $name_column->query('tag:a')->one()->getText());

		$this->assertEquals($key, (strpos($url, 'latest')
				? $name_column->query('xpath://span[@class="green"]')->one()->getText()
				: $table->findRow('Name', $name)->getColumn('Key')->getText()
			)
		);
	}

	public function getCreateVaultMacrosData() {
		return [
			[
				[
					'expected' => TEST_GOOD,
					'macro_fields' => [
						'macro' => '{$VAULT_MACRO}',
						'value' => [
							'text' => 'secret/path:key',
							'type' => 'Vault secret'
						],
						'description' => 'vault description'
					],
					'title' => 'Host updated'
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'macro_fields' => [
						'macro' => '{$VAULT_MACRO2}',
						'value' => [
							'text' => 'one/two/three/four/five/six:key',
							'type' => 'Vault secret'
						],
						'description' => 'vault description7'
					],
					'title' => 'Host updated'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'macro_fields' => [
						'macro' => '{$VAULT_MACRO3}',
						'value' => [
							'text' => 'secret/path:',
							'type' => 'Vault secret'
						],
						'description' => 'vault description2'
					],
					'title' => 'Cannot update host',
					'message' => 'Invalid parameter "/1/macros/6/value": incorrect syntax near "path:".'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'macro_fields' => [
						'macro' => '{$VAULT_MACRO4}',
						'value' => [
							'text' => '/path:key',
							'type' => 'Vault secret'
						],
						'description' => 'vault description3'
					],
					'title' => 'Cannot update host',
					'message' => 'Invalid parameter "/1/macros/6/value": incorrect syntax near "/path:key".'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'macro_fields' => [
						'macro' => '{$VAULT_MACRO5}',
						'value' => [
							'text' => 'path:key',
							'type' => 'Vault secret'
						],
						'description' => 'vault description4'
					],
					'title' => 'Cannot update host',
					'message' => 'Invalid parameter "/1/macros/6/value": incorrect syntax near "path:key".'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'macro_fields' => [
						'macro' => '{$VAULT_MACRO6}',
						'value' => [
							'text' => ':key',
							'type' => 'Vault secret'
						],
						'description' => 'vault description5'
					],
					'title' => 'Cannot update host',
					'message' => 'Invalid parameter "/1/macros/6/value": incorrect syntax near ":key".'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'macro_fields' => [
						'macro' => '{$VAULT_MACRO7}',
						'value' => [
							'text' => 'secret/path',
							'type' => 'Vault secret'
						],
						'description' => 'vault description6'
					],
					'title' => 'Cannot update host',
					'message' => 'Invalid parameter "/1/macros/6/value": incorrect syntax near "path".'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'macro_fields' => [
						'macro' => '{$VAULT_MACRO8}',
						'value' => [
							'text' => '/secret/path:key',
							'type' => 'Vault secret'
						],
						'description' => 'vault description8'
					],
					'title' => 'Cannot update host',
					'message' => 'Invalid parameter "/1/macros/6/value": incorrect syntax near "/secret/path:key".'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'macro_fields' => [
						'macro' => '{$VAULT_MACRO9}',
						'value' => [
							'text' => '',
							'type' => 'Vault secret'
						],
						'description' => 'vault description9'
					],
					'title' => 'Cannot update host',
					'message' => 'Invalid parameter "/1/macros/6/value": cannot be empty.'
				]
			]
		];
	}

	/**
	 * @dataProvider getCreateVaultMacrosData
	 *
	 */
	public function testFormMacrosHost_CreateVaultMacros($data) {
		$this->createVaultMacros($data, 'zabbix.php?action=host.view', 'hosts', 'Available host');
	}

	public function getUpdateVaultMacrosData() {
		return [
			[
				[
					'action' => USER_ACTION_UPDATE,
					'index' => 2,
					'macro' => '{$VAULT_HOST_MACRO3_CHANGED}',
					'value' => [
						'text' => 'secret/path:key'
					],
					'description' => ''
				]
			],
			[
				[
					'action' => USER_ACTION_UPDATE,
					'index' => 2,
					'macro' => '{$VAULT_HOST_MACRO3_CHANGED}',
					'value' => [
						'text' => 'new/path/to/secret:key'
					],
					'description' => ''
				]
			],
			[
				[
					'action' => USER_ACTION_UPDATE,
					'index' => 2,
					'macro' => '{$VAULT_HOST_MACRO3_CHANGED}',
					'value' => [
						'text' => 'new/path/to/secret:key'
					],
					'description' => 'Changing description'
				]
			]
		];
	}

	/**
	 * @dataProvider getUpdateVaultMacrosData
	 */
	public function testFormMacrosHost_UpdateVaultMacros($data) {
		$this->updateVaultMacros($data, 'zabbix.php?action=host.view', 'hosts', 'Host for suppression');
	}
}
