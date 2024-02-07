<?php
/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
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


require_once dirname(__FILE__).'/../../include/CWebTest.php';
require_once dirname(__FILE__).'/../behaviors/CMessageBehavior.php';
require_once dirname(__FILE__).'/../behaviors/CTableBehavior.php';

class testPagePrototypes extends CWebTest {

	/**
	 * Attach MessageBehavior and TableBehavior to the test.
	 *
	 * @return array
	 */
	public function getBehaviors() {
		return [
			CMessageBehavior::class,
			CTableBehavior::class
		];
	}

	/**
	 * What page should be checked - item/trigger/graph/host prototype.
	 */
	public $page_name;

	/**
	 * How much item/trigger/graph/host displayed in table result opening page.
	 */
	public static $entity_count;

	/**
	 * Name of item/trigger/host prototype that has tags.
	 */
	public $tag;

	/**
	 * Layouts of prototype page and SQL used in delete scenarios.
	 */
	protected $layout_sql_data = [
		'graph' => [
			'headers' => ['', 'Name', 'Width', 'Height', 'Graph type', 'Discover'],
			'clickable_headers' => ['Name', 'Graph type', 'Discover'],
			'buttons' => [
				'Delete' => false,
				'Create graph prototype' => true
			],
			'sql' => 'SELECT null FROM graphs WHERE graphid='
		],
		'host' => [
			'headers' => ['', 'Name', 'Templates', 'Create enabled', 'Discover', 'Tags'],
			'clickable_headers' => ['Name', 'Create enabled', 'Discover'],
			'buttons' => [
				'Create enabled' => false,
				'Create disabled' => false,
				'Delete' => false,
				'Create host prototype' => true
			],
			'sql' => 'SELECT null FROM hosts WHERE hostid='
		],
		'item' => [
			'headers' => ['', '', 'Name', 'Key', 'Interval', 'History', 'Trends', 'Type', 'Create enabled', 'Discover', 'Tags'],
			'clickable_headers' => ['Name', 'Key', 'Interval', 'History', 'Trends', 'Type', 'Create enabled', 'Discover'],
			'buttons' => [
				'Create enabled' => false,
				'Create disabled' => false,
				'Mass update' => false,
				'Delete' => false,
				'Create item prototype' => true
			],
			'sql' => 'SELECT null FROM items WHERE itemid='
		],
		'trigger' => [
			'headers' => ['', 'Severity', 'Name', 'Operational data', 'Expression', 'Create enabled', 'Discover', 'Tags'],
			'clickable_headers' => ['Severity', 'Name', 'Create enabled', 'Discover'],
			'buttons' => [
				'Create enabled' => false,
				'Create disabled' => false,
				'Mass update' => false,
				'Delete' => false,
				'Create trigger prototype' => true
			],
			'sql' => 'SELECT null FROM triggers WHERE triggerid='
		]
	];

	/**
	 * Check layout on prototype page.
	 *
	 * @param boolean $template    if true - prototype page in template should be checked
	 */
	public function checkLayout($template = false) {
		// Checking Title, Header and Column names.
		$this->page->assertTitle('Configuration of '.$this->page_name.' prototypes');
		$page_header  = ucfirst($this->page_name).' prototypes';
		$this->page->assertHeader($page_header);
		$table = $this->query('class:list-table')->asTable()->one();
		$this->assertSame($this->layout_sql_data[$this->page_name]['headers'], $table->getHeadersText());
		$this->assertTableStats(self::$entity_count);

		// Check that Breadcrumbs exists.
		$links = ($template) ? ['All templates', 'Template for prototype check'] : ['All hosts', 'Host for prototype check'];
		$breadcrumbs = [
			'Discovery list',
			'Drule for prototype check',
			'Item prototypes',
			'Trigger prototypes',
			'Graph prototypes',
			'Host prototypes'
		];
		$this->assertEquals(array_merge($links, $breadcrumbs),
				$this->query('xpath://div[@class="header-navigation"]//a')->all()->asText()
		);

		// Check counter value next to entity breadcrumb.
		$this->assertEquals(self::$entity_count, $this->query('xpath://div[@class="header-navigation"]//a[text()='.
				CXPathHelper::escapeQuotes($page_header).']/following-sibling::sup')->one()->getText()
		);

		// Check displayed buttons and their default status after opening prototype page.
		foreach ($this->layout_sql_data[$this->page_name]['buttons'] as $button => $status) {
			$this->assertTrue($this->query('button', $button)->one()->isEnabled($status));
		}

		switch ($this->page_name) {
			case 'graph':
				// Check Width and Height columns for graph prototype page.
				foreach (['Width', 'Height'] as $column) {
					$this->assertTableDataColumn([100, 200, 300, 400], $column);
				}

				break;

			case 'item':
				// Check additional popup configuration for item prototype page.
				$table->getRow(0)->query('xpath:.//button')->one()->click();
				$popup_menu = CPopupMenuElement::find()->waitUntilPresent()->one();
				$this->assertEquals(['CONFIGURATION'], $popup_menu->getTitles()->asText());
				$this->assertEquals(['Item prototype', 'Trigger prototypes', 'Create trigger prototype', 'Create dependent item'],
						$popup_menu->getItems()->asText()
				);

				$menu_item_statuses = [
					'Item prototype' => true,
					'Trigger prototypes' => false,
					'Create trigger prototype' => true,
					'Create dependent item' => true
				];

				foreach ($menu_item_statuses as $item => $enabled) {
					$this->assertTrue($popup_menu->getItem($item)->isEnabled($enabled));
				}

				$popup_menu->close();

				break;

			case 'host':
				// Check Template column for host prototype page.
				$template_row = $table->findRow('Name', '4 Host prototype monitored not discovered {#H}');
				$template_row->assertValues(['Templates' => 'Template for host prototype']);
				$this->assertTrue($template_row->getColumn('Templates')->isClickable());

				break;

			case 'trigger':
				// Check Operational data and expression column - values should be displayed, on trigger prototype page.
				$opdata = [
					'12345',
					'{#PROT_MAC}',
					'test',
					'!@#$%^&*',
					'{$TEST}',
					'🙂🙃'
				];
				$this->assertTableDataColumn($opdata, 'Operational data');
				$trigger_row = $table->getRow(0);

				$expression = ($template) ? 'Template' : 'Host';

				$this->assertEquals('last(/'.$expression.' for prototype check/1_key[{#KEY}])=0',
					$trigger_row->getColumn('Expression')->getText()
				);
				$this->assertTrue($trigger_row->getColumn('Expression')->isClickable());

				break;
		}

		// Check tags (Graph prototypes doesn't have any tags).
		if ($this->page_name !== 'graph') {
			$tags = $table->findRow('Name', $this->tag)->getColumn('Tags')->query('class:tag')->all();
			$this->assertEquals(['name_1: value_1', 'name_2: value_2'], $tags->asText());

			foreach ($tags as $tag) {
				$tag->click();
				$hint = $this->query('xpath://div[@data-hintboxid]')->asOverlayDialog()->waitUntilPresent()->all()->last();
				$this->assertEquals($tag->getText(), $hint->getText());
				$hint->close();
			}
		}

		// Check clickable headers.
		foreach ($this->layout_sql_data[$this->page_name]['clickable_headers'] as $header) {
			$this->assertTrue($table->query('link', $header)->one()->isClickable());
		}
	}

	/**
	 * Host prototype sorting.
	 */
	public static function getHostsSortingData() {
		return [
			// #0 Sort by Name.
			[
				[
					'sort_by' => 'Name',
					'sort' => 'name',
					'result' => [
						'1 Host prototype monitored discovered {#H}',
						'2 Host prototype not monitored discovered {#H}',
						'3 Host prototype not monitored not discovered {#H}',
						'4 Host prototype monitored not discovered {#H}'
					]
				]
			],
			// #1 Sort by Create enabled.
			[
				[
					'sort_by' => 'Create enabled',
					'sort' => 'status',
					'result' => [
						'Yes',
						'Yes',
						'No',
						'No'
					]
				]
			],
			// #2 Sort by Discover.
			[
				[
					'sort_by' => 'Discover',
					'sort' => 'discover',
					'result' => [
						'Yes',
						'Yes',
						'No',
						'No'
					]
				]
			]
		];
	}

	/**
	 * Item prototype sorting.
	 */
	public static function getItemsSortingData() {
		return [
			// #0 Sort by Name.
			[
				[
					'sort_by' => 'Name',
					'sort' => 'name',
					'result' => [
						'1 Item prototype monitored discovered',
						'2 Item prototype not monitored discovered',
						'3 Item prototype not monitored not discovered',
						'4 Item prototype monitored not discovered',
						'5 Item prototype trapper with text type'
					]
				]
			],
			// #1 Sort by Key.
			[
				[
					'sort_by' => 'Key',
					'sort' => 'key_',
					'result' => [
						'1_key[{#KEY}]',
						'2_key[{#KEY}]',
						'3_key[{#KEY}]',
						'4_key[{#KEY}]',
						'5_key[{#KEY}]'
					]
				]
			],
			// #2 Sort by Interval.
			[
				[
					'sort_by' => 'Interval',
					'sort' => 'delay',
					'result' => [
						'',
						15,
						30,
						45,
						60
					]
				]
			],
			// #3 Sort by History.
			[
				[
					'sort_by' => 'History',
					'sort' => 'history',
					'result' => [
						'0',
						'60d',
						'70d',
						'80d',
						'90d'
					]
				]
			],
			// #4 Sort by Trends.
			[
				[
					'sort_by' => 'Trends',
					'sort' => 'trends',
					'result' => [
						'',
						'200d',
						'250d',
						'300d',
						'350d'
					]
				]
			],
			// #5 Sort by Type.
			[
				[
					'sort_by' => 'Type',
					'sort' => 'type',
					'result' => [
						'Zabbix trapper',
						'Zabbix internal',
						'Zabbix agent (active)',
						'Calculated',
						'HTTP agent'
					]
				]
			],
			// #6 Sort by Create enabled.
			[
				[
					'sort_by' => 'Create enabled',
					'sort' => 'status',
					'result' => [
						'Yes',
						'Yes',
						'Yes',
						'No',
						'No'
					]
				]
			],
			// #7 Sort by Discover.
			[
				[
					'sort_by' => 'Discover',
					'sort' => 'discover',
					'result' => [
						'Yes',
						'Yes',
						'Yes',
						'No',
						'No'
					]
				]
			]
		];
	}

	/**
	 * Trigger prototype sorting.
	 */
	public static function getTriggersSortingData() {
		return [
			// #0 Sort by Severity.
			[
				[
					'sort_by' => 'Severity',
					'sort' => 'priority',
					'result' => [
						'Not classified',
						'Information',
						'Warning',
						'Average',
						'High',
						'Disaster'
					]
				]
			],
			// #1 Sort by Name.
			[
				[
					'sort_by' => 'Name',
					'sort' => 'description',
					'result' => [
						'1 Trigger prototype monitored discovered_{#KEY}',
						'2 Trigger prototype not monitored discovered_{#KEY}',
						'3 Trigger prototype not monitored not discovered_{#KEY}',
						'4 Trigger prototype monitored not discovered_{#KEY}',
						'5 Trigger prototype for high severity_{#KEY}',
						'6 Trigger prototype for disaster severity_{#KEY}'
					]
				]
			],
			// #2 Sort by Create enabled.
			[
				[
					'sort_by' => 'Create enabled',
					'sort' => 'status',
					'result' => [
						'Yes',
						'Yes',
						'Yes',
						'Yes',
						'No',
						'No'
					]
				]
			],
			// #3 Sort by Discover.
			[
				[
					'sort_by' => 'Discover',
					'sort' => 'discover',
					'result' => [
						'Yes',
						'Yes',
						'Yes',
						'Yes',
						'No',
						'No'
					]
				]
			]
		];
	}

	/**
	 * Graph prototype sorting.
	 */
	public static function getGraphsSortingData() {
		return [
			// #0 Sort by Name.
			[
				[
					'sort_by' => 'Name',
					'sort' => 'name',
					'result' => [
						'1 Graph prototype discovered_{#KEY}',
						'2 Graph prototype not discovered_{#KEY}',
						'3 Graph prototype pie discovered_{#KEY}',
						'4 Graph prototype exploded not discovered_{#KEY}'
					]
				]
			],
			// #1 Sort by Graph type.
			[
				[
					'sort_by' => 'Graph type',
					'sort' => 'graphtype',
					'result' => [
						'Exploded',
						'Normal',
						'Pie',
						'Stacked'
					]
				]
			],
			// #2 Sort by Discover.
			[
				[
					'sort_by' => 'Discover',
					'sort' => 'discover',
					'result' => [
						'Yes',
						'Yes',
						'No',
						'No'
					]
				]
			]
		];
	}

	/**
	 * Check available sorting on prototype page.
	 *
	 * @param array $data		data from data provider
	 */
	public function executeSorting($data) {
		$table = $this->query('class:list-table')->asTable()->one();
		foreach (['desc', 'asc'] as $sorting) {
			$table->query('link', $data['sort_by'])->one()->click();
			$expected = ($sorting === 'asc') ? $data['result'] : array_reverse($data['result']);
			$this->assertEquals($expected, $this->getTableColumnData($data['sort_by']));
		}
	}

	/**
	 * Host prototype disable/enable by link and button.
	 */
	public static function getHostsButtonLinkData() {
		return [
			// #0 Click on Create disabled button.
			[
				[
					'name' => '1 Host prototype monitored discovered {#H}',
					'button' => 'Create disabled',
					'column_check' => 'Create enabled',
					'before' => 'Yes',
					'after' => 'No'
				]
			],
			// #1 Click on Create enabled button.
			[
				[
					'name' => '2 Host prototype not monitored discovered {#H}',
					'button' => 'Create enabled',
					'column_check' => 'Create enabled',
					'before' => 'No',
					'after' => 'Yes'
				]
			],
			// #2 Enabled clicking on link in Create enabled column.
			[
				[
					'name' => '3 Host prototype not monitored not discovered {#H}',
					'column_check' => 'Create enabled',
					'before' => 'No',
					'after' => 'Yes'
				]
			],
			// #3 Disabled clicking on link in Create enabled column.
			[
				[
					'name' => '4 Host prototype monitored not discovered {#H}',
					'column_check' => 'Create enabled',
					'before' => 'Yes',
					'after' => 'No'
				]
			],
			// #4 Enable discovering clicking on link in Discover column.
			[
				[
					'name' => '3 Host prototype not monitored not discovered {#H}',
					'column_check' => 'Discover',
					'before' => 'No',
					'after' => 'Yes'
				]
			],
			// #5 Disable discovering clicking on link in Discover column.
			[
				[
					'name' => '2 Host prototype not monitored discovered {#H}',
					'column_check' => 'Discover',
					'before' => 'Yes',
					'after' => 'No'
				]
			],
			// #6 Enable all host prototypes clicking on Create enabled button.
			[
				[
					'button' => 'Create enabled',
					'column_check' => 'Create enabled',
					'after' => ['Yes', 'Yes', 'Yes', 'Yes']
				]
			],
			// #7 Disable all host prototypes clicking on Create disabled button.
			[
				[
					'button' => 'Create disabled',
					'column_check' => 'Create enabled',
					'after' => ['No', 'No', 'No', 'No']
				]
			]
		];
	}

	/**
	 * Item prototype disable/enable by link and button.
	 */
	public static function getItemsButtonLinkData() {
		return [
			// #0 Click on Create disabled button.
			[
				[
					'name' => '1 Item prototype monitored discovered',
					'button' => 'Create disabled',
					'column_check' => 'Create enabled',
					'before' => 'Yes',
					'after' => 'No'
				]
			],
			// #1 Click on Create enabled button.
			[
				[
					'name' => '2 Item prototype not monitored discovered',
					'button' => 'Create enabled',
					'column_check' => 'Create enabled',
					'before' => 'No',
					'after' => 'Yes'
				]
			],
			// #2 Enabled clicking on link in Create enabled column.
			[
				[
					'name' => '3 Item prototype not monitored not discovered',
					'column_check' => 'Create enabled',
					'before' => 'No',
					'after' => 'Yes'
				]
			],
			// #3 Disabled clicking on link in Create enabled column.
			[
				[
					'name' => '4 Item prototype monitored not discovered',
					'column_check' => 'Create enabled',
					'before' => 'Yes',
					'after' => 'No'
				]
			],
			// #4 Enable discovering clicking on link in Discover column.
			[
				[
					'name' => '3 Item prototype not monitored not discovered',
					'column_check' => 'Discover',
					'before' => 'No',
					'after' => 'Yes'
				]
			],
			// #5 Disable discovering clicking on link in Discover column.
			[
				[
					'name' => '2 Item prototype not monitored discovered',
					'column_check' => 'Discover',
					'before' => 'Yes',
					'after' => 'No'
				]
			],
			// #6 Enable all host prototypes clicking on Create enabled button.
			[
				[
					'button' => 'Create enabled',
					'column_check' => 'Create enabled',
					'after' => ['Yes', 'Yes', 'Yes', 'Yes', 'Yes']
				]
			],
			// #7 Disable all host prototypes clicking on Create disabled button.
			[
				[
					'button' => 'Create disabled',
					'column_check' => 'Create enabled',
					'after' => ['No', 'No', 'No', 'No', 'No']
				]
			]
		];
	}

	/**
	 * Trigger prototype disable/enable by link and button.
	 */
	public static function getTriggersButtonLinkData() {
		return [
			// #0 Click on Create disabled button.
			[
				[
					'name' => '1 Trigger prototype monitored discovered_{#KEY}',
					'button' => 'Create disabled',
					'column_check' => 'Create enabled',
					'before' => 'Yes',
					'after' => 'No'
				]
			],
			// #1 Click on Create enabled button.
			[
				[
					'name' => '2 Trigger prototype not monitored discovered_{#KEY}',
					'button' => 'Create enabled',
					'column_check' => 'Create enabled',
					'before' => 'No',
					'after' => 'Yes'
				]
			],
			// #2 Enabled clicking on link in Create enabled column.
			[
				[
					'name' => '3 Trigger prototype not monitored not discovered_{#KEY}',
					'column_check' => 'Create enabled',
					'before' => 'No',
					'after' => 'Yes'
				]
			],
			// #3 Disabled clicking on link in Create enabled column.
			[
				[
					'name' => '4 Trigger prototype monitored not discovered_{#KEY}',
					'column_check' => 'Create enabled',
					'before' => 'Yes',
					'after' => 'No'
				]
			],
			// #4 Enable discovering clicking on link in Discover column.
			[
				[
					'name' => '3 Trigger prototype not monitored not discovered_{#KEY}',
					'column_check' => 'Discover',
					'before' => 'No',
					'after' => 'Yes'
				]
			],
			// #5 Disable discovering clicking on link in Discover column.
			[
				[
					'name' => '2 Trigger prototype not monitored discovered_{#KEY}',
					'column_check' => 'Discover',
					'before' => 'Yes',
					'after' => 'No'
				]
			],
			// #6 Enable all trigger prototypes clicking on Create enabled button.
			[
				[
					'button' => 'Create enabled',
					'column_check' => 'Create enabled',
					'after' => ['Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes']
				]
			],
			// #7 Disable all trigger prototypes clicking on Create disabled button.
			[
				[
					'button' => 'Create disabled',
					'column_check' => 'Create enabled',
					'after' => ['No', 'No', 'No', 'No', 'No', 'No']
				]
			]
		];
	}

	/**
	 * Graph prototype disable/enable by link and button.
	 */
	public static function getGraphsButtonLinkData() {
		return [
			// #0 Enable discovering clicking on link in Discover column.
			[
				[
					'name' => '2 Graph prototype not discovered_{#KEY}',
					'column_check' => 'Discover',
					'before' => 'No',
					'after' => 'Yes'
				]
			],
			// #1 Disable discovering clicking on link in Discover column.
			[
				[
					'name' => '1 Graph prototype discovered_{#KEY}',
					'column_check' => 'Discover',
					'before' => 'Yes',
					'after' => 'No'
				]
			]
		];
	}

	/**
	 * Check Create enabled/disabled buttons and links from Create enabled and Discover columns.
	 *
	 * @param array $data		data from data provider
	 */
	public function checkTableAction($data) {
		$table = $this->query('class:list-table')->asTable()->one();

		// Find prototype in table by name and check column data before update.
		if (array_key_exists('name', $data)) {
			$row = $table->findRow('Name', $data['name']);
			$this->assertEquals($data['before'], $row->getColumn($data['column_check'])->getText());
		}

		// Click on button or on link in column (Create enabled or Discover).
		if (array_key_exists('button', $data)) {
			// If there is no prototype name in data provider, then select all existing in table host prototypes.
			$selected = (array_key_exists('name', $data)) ? $data['name'] : null;
			$this->selectTableRows($selected);
			$this->query('button', $data['button'])->one()->click();
			$this->page->acceptAlert();
			$this->page->waitUntilReady();
		}
		else {
			// Click on link in table.
			$row->getColumn($data['column_check'])->query('link', $data['before'])->waitUntilClickable()->one()->click();
			$this->page->waitUntilReady();
		}

		// Check column value for one or for all prototypes.
		if (array_key_exists('name', $data)) {
			$this->assertMessage(TEST_GOOD, ucfirst($this->page_name).' prototype updated');
			$this->assertEquals($data['after'], $row->getColumn($data['column_check'])->getText());
		}
		else {
			$this->assertMessage(TEST_GOOD, ucfirst($this->page_name).' prototypes updated');
			$this->assertTableDataColumn($data['after'], $data['column_check']);
		}
	}

	/**
	 * Host prototype delete.
	 */
	public static function getHostsDeleteData() {
		return [
			// #0 Cancel delete.
			[
				[
					'name' => ['1 Host prototype monitored discovered {#H}'],
					'cancel' => true
				]
			],
			// #1 Delete one.
			[
				[
					'name' => ['2 Host prototype not monitored discovered {#H}'],
					'message' => 'Host prototype deleted'
				]
			],
			// #2 Delete more than 1.
			[
				[
					'name' => [
						'3 Host prototype not monitored not discovered {#H}',
						'4 Host prototype monitored not discovered {#H}'
					],
					'message' => 'Host prototypes deleted'
				]
			]
		];
	}

	/**
	 * Item prototype delete.
	 */
	public static function getItemsDeleteData() {
		return [
			// #0 Cancel delete.
			[
				[
					'name' => ['1 Item prototype monitored discovered'],
					'cancel' => true
				]
			],
			// #1 Delete one.
			[
				[
					'name' => ['2 Item prototype not monitored discovered'],
					'message' => 'Item prototype deleted'
				]
			],
			// #2 Delete more than 1.
			[
				[
					'name' => [
						'3 Item prototype not monitored not discovered',
						'4 Item prototype monitored not discovered'
					],
					'message' => 'Item prototypes deleted'
				]
			]
		];
	}

	/**
	 * Trigger prototype delete.
	 */
	public static function getTriggersDeleteData() {
		return [
			// #0 Cancel delete.
			[
				[
					'name' => ['1 Trigger prototype monitored discovered_{#KEY}'],
					'cancel' => true
				]
			],
			// #1 Delete one.
			[
				[
					'name' => ['2 Trigger prototype not monitored discovered_{#KEY}'],
					'message' => 'Trigger prototype deleted'
				]
			],
			// #2 Delete more than 1.
			[
				[
					'name' => [
						'3 Trigger prototype not monitored not discovered_{#KEY}',
						'4 Trigger prototype monitored not discovered_{#KEY}'
					],
					'message' => 'Trigger prototypes deleted'
				]
			]
		];
	}

	/**
	 * Graph prototype delete.
	 */
	public static function getGraphsDeleteData() {
		return [
			// #0 Cancel delete.
			[
				[
					'name' => ['1 Graph prototype discovered_{#KEY}'],
					'cancel' => true
				]
			],
			// #1 Delete one.
			[
				[
					'name' => ['2 Graph prototype not discovered_{#KEY}'],
					'message' => 'Graph prototype deleted'
				]
			],
			// #2 Delete more than 1.
			[
				[
					'name' => [
						'3 Graph prototype pie discovered_{#KEY}',
						'4 Graph prototype exploded not discovered_{#KEY}'
					],
					'message' => 'Graph prototypes deleted'
				]
			]
		];
	}

	/**
	 * Check Delete scenarios.
	 *
	 * @param array $data    data provider
	 * @param array $ids     ID's of deleted entity
	 */
	public function checkDelete($data, $ids) {
		// Check that prototype exists in DB.
		foreach ($ids as $id) {
			$this->assertEquals(1, CDBHelper::getCount($this->layout_sql_data[$this->page_name]['sql'].$id));
		}

		// Check that prototype exists and displayed in prototype table.
		$prototype_names = $this->getTableColumnData('Name');
		foreach ($data['name'] as $name) {
			$this->assertTrue(in_array($name, $prototype_names));
		}

		// Select prototype and click on Delete button.
		$this->selectTableRows($data['name']);
		$this->query('button:Delete')->one()->click();

		// Check that after canceling Delete, prototype still exists in DB and displayed in table.
		if (array_key_exists('cancel', $data)) {
			$this->page->dismissAlert();
			foreach ($data['name'] as $name) {
				$this->assertTrue(in_array($name, $prototype_names));
			}
		}
		else {
			$this->page->acceptAlert();
			$this->page->waitUntilReady();
			$this->assertMessage(TEST_GOOD, $data['message']);

			// Check that prototype doesn't exist and not displayed in prototype table.
			foreach ($data['name'] as $name) {
				$this->assertFalse(in_array($name, $this->getTableColumnData('Name')));
			}
		}

		$count = (array_key_exists('cancel', $data)) ? 1 : 0;

		// Check prototype in DB.
		foreach ($ids as $id) {
			$this->assertEquals($count, CDBHelper::getCount($this->layout_sql_data[$this->page_name]['sql'].$id));
		}
	}

	/**
	 * Check value display in table for item prototype page.
	 */
	public static function getItemsNotDisplayedValuesData() {
		return [
			// #0 SNMP trapper without interval.
			[
				[
					'fields' => [
						'Name' => 'Empty SNMP interval',
						'Type' => 'SNMP trap',
						'Key' => 'snmp_interval_[{#KEY}]'
					],
					'check' => [
						'Interval' => ''
					]
				]
			],
			// #1 Zabbix trapper without interval.
			[
				[
					'fields' => [
						'Name' => 'Empty Zabbix trapper interval',
						'Type' => 'Zabbix trapper',
						'Key' => 'zabbix_trapper_interval_[{#KEY}]'
					],
					'check' => [
						'Interval' => ''
					]
				]
			],
			// #2 Dependent item without interval.
			[
				[
					'fields' => [
						'Name' => 'Empty dependent item interval',
						'Type' => 'Dependent item',
						'Master item' => 'Master item',
						'Key' => 'dependent_interval_[{#KEY}]'
					],
					'check' => [
						'Interval' => ''
					]
				]
			],
			// #3 Zabbix agent with type of information - text.
			[
				[
					'fields' => [
						'Name' => 'Text zabbix trapper',
						'Type' => 'Zabbix trapper',
						'Type of information' => 'Text',
						'Key' => 'text_[{#KEY}]'
					],
					'check' => [
						'Trends' => ''
					]
				]
			],
			// #4 Zabbix agent with type of information - character.
			[
				[
					'fields' => [
						'Name' => 'Character zabbix trapper',
						'Type' => 'Zabbix trapper',
						'Type of information' => 'Character',
						'Key' => 'character_[{#KEY}]'
					],
					'check' => [
						'Trends' => ''
					]
				]
			],
			// #5 Zabbix agent with type of information - log.
			[
				[
					'fields' => [
						'Name' => 'Log zabbix trapper',
						'Type' => 'Zabbix trapper',
						'Type of information' => 'Log',
						'Key' => 'log_[{#KEY}]'
					],
					'check' => [
						'Trends' => ''
					]
				]
			]
		];
	}

	/**
	 * Check that empty values displayed in Trends and Interval columns. SNMP, Zabbix trappers has empty values in trends column.
	 * Dependent items has empty update interval column.
	 * Only for Item prototype.
	 */
	public function checkNotDisplayedValues($data) {
		$this->query('button:Create item prototype')->one()->click();
		$form = $this->query('name:itemForm')->waitUntilPresent()->asForm()->one();
		$form->fill($data['fields']);
		$form->submit()->waitUntilNotVisible();
		$this->page->waitUntilReady();

		$table = $this->query('class:list-table')->asTable()->one();
		$template_row = $table->findRow('Key', $data['fields']['Key']);
		$template_row->assertValues($data['check']);
	}
}
