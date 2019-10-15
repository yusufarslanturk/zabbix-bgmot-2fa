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

require_once dirname(__FILE__).'/../include/CWebTest.php';

class testPageLatestDataItemDescription extends CWebTest {

	public static function getLatestData() {
		return [
			// Item without description.
			[
				[
					'Item name' => 'item_testPageHistory_CheckLayout_Log'
				]
			],
			// Item with plain text in the description.
			[
				[
					'Item name' => 'item_testPageHistory_CheckLayout_Log_2',
					'Description' => 'Non-clickable description',
					'Icon' => true
				]
			],
			// Item with only 1 url in description.
			[
				[
					'Item name' => 'item_testPageHistory_CheckLayout_Eventlog',
					'URLs' => [
						'https://zabbix.com'
					],
					'Icon' => true
				]
			],
			// Item with text and url in description.
			[
				[
					'Item name' => 'item_testPageHistory_CheckLayout_Eventlog_2',
					'Description' => 'The following url should be clickable: ',
					'URLs' => [
						'https://zabbix.com'
					],
					'Icon' => true
				]
			],
			// Item with multiple urls in description.
			[
				[
					'Item name' => 'item_testPageHistory_CheckLayout_Character',
					'URLs' => [
						'https://zabbix.com',
						'https://www.zabbix.com/career',
						'https://www.zabbix.com/contact'
					],
					'Icon' => true
				]
			],
			// Item with text and 2 urls in description.
			[
				[
					'Item name' => 'item_testPageHistory_CheckLayout_Text',
					'Description' => 'These urls should be clickable: ',
					'URLs' => [
						'https://zabbix.com',
						'https://www.zabbix.com/career'
					],
					'Icon' => true
				]
			]
		];
	}

	/**
	 * @dataProvider getLatestData
	 */
	public function testHostAvailabilityWidget_Create($data) {
		// Open Latest data for host 'testPageHistory_CheckLayout'
		$this->page->login()->open('latest.php?hostids%5B%5D=15003&application=&select=&show_without_data=1&filter_set=1');
		$output = $this->query('class:overflow-ellipsis')->asTable()->one();

		// Find rows from the data provider and click on the description icon if such should persist.
		foreach ($output->getRows() as $row) {
			if ($row->query("xpath:.//span[text()='".$data['Item name']."']")->count() === 1) {
				if (CTestArrayHelper::get($data,'Icon', false)) {
					$row->query('class:icon-description')->one()->click()->waitUntilReady();
					// Construct the expected content of the tooltip from the urls and descriptions in data provider.
					$expected = null;
					if (array_key_exists('Description', $data)) {
						$expected = $data['Description'];
					}
					if (array_key_exists('URLs', $data)) {
						$expected = $expected.$data['URLs'][0];
						if (sizeof($data['URLs']) > 1) {
							for ($i = 1; $i < sizeof($data['URLs']); $i++) {
								$expected = $expected.' '.$data['URLs'][$i];
							}
						}
						// Verify that each of the urls is clickable.
						foreach ($data['URLs'] as $url) {
							$this->assertTrue($this->query("xpath://div[@class = 'overlay-dialogue']/a[@href='".$url."']")->one()->isClickable());
						}
					}
					// Verify the real description with the expected one.
					$this->assertEquals($expected, $this->query("xpath://div[@class = 'overlay-dialogue']")->one()->getText());

					// Verify that the tool-tip can be closed.
					$this->query("xpath://div[@class = 'overlay-dialogue']/button[@title='Close']")->one()->click();
					$this->assertTrue($this->query("xpath://div[@class = 'overlay-dialogue']")->count() === 0);
				}
				// If the item has no description the description icon should not be there.
				else {
					$this->assertTrue($row->query("xpath:.//span[contains(@class, 'icon-description')]")->count() === 0);
				}
			}
		}
	}
}
