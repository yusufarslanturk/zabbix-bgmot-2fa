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
require_once dirname(__FILE__).'/../../include/items.inc.php';
require_once dirname(__FILE__).'/traits/PreprocessingTrait.php';

/**
 * @backup items
 */
class testFormItemPreprocessingTest extends CWebTest {

	const HOST_ID = 40001;		//'Simple form test host'

	use PreprocessingTrait;

	public static function getTestData() {
		return [
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Item Preprocessing Test',
						'Key' => 'item-preprocessing-test'
					],
					'preprocessing' => [
						['type' => 'Regular expression', 'parameter_1' => 'expression', 'parameter_2' => 'test output'],
						['type' => 'Trim', 'parameter_1' => '1a2b3c'],
						['type' => 'Right trim', 'parameter_1' => 'abc'],
						['type' => 'Left trim', 'parameter_1' => 'def'],
						['type' => 'XML XPath', 'parameter_1' => 'path'],
						['type' => 'JSONPath', 'parameter_1' => 'path'],
						['type' => 'Custom multiplier', 'parameter_1' => '123'],
						['type' => 'Simple change'],
						['type' => 'Change per second'],
						['type' => 'Boolean to decimal'],
						['type' => 'Octal to decimal'],
						['type' => 'Hexadecimal to decimal'],
						['type' => 'JavaScript', 'parameter_1' => 'Test JavaScript'],
						['type' => 'In range', 'parameter_1' => '-5', 'parameter_2' => '9.5'],
						['type' => 'Matches regular expression', 'parameter_1' => 'expression'],
						['type' => 'Does not match regular expression', 'parameter_1' => 'expression'],
						['type' => 'Check for error in JSON', 'parameter_1' => 'path'],
						['type' => 'Check for error in XML', 'parameter_1' => 'path'],
						['type' => 'Check for error using regular expression', 'parameter_1' => 'path', 'parameter_2' => 'output'],
						['type' => 'Discard unchanged'],
						['type' => 'Discard unchanged with heartbeat', 'parameter_1' => '1'],
						['type' => 'Prometheus pattern', 'parameter_1' => 'cpu_usage_system', 'parameter_2' => 'label_name'],
						['type' => 'Prometheus to JSON', 'parameter_1' => '']
					]
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Preprocessing Test Validation',
						'Key' => 'item-preprocessing-test-validation'
					],
					'preprocessing' => [
						['type' => 'Regular expression', 'parameter_1' => '', 'parameter_2' => ''],
						['type' => 'Trim', 'parameter_1' => ''],
						['type' => 'Right trim', 'parameter_1' => ''],
						['type' => 'Left trim', 'parameter_1' => ''],
						['type' => 'XML XPath', 'parameter_1' => ''],
						['type' => 'JSONPath', 'parameter_1' => ''],
						['type' => 'Custom multiplier', 'parameter_1' => ''],
						['type' => 'JavaScript', 'parameter_1' => ''],
						['type' => 'In range', 'parameter_1' => '', 'parameter_2' => ''],
						['type' => 'Matches regular expression', 'parameter_1' => ''],
						['type' => 'Does not match regular expression', 'parameter_1' => ''],
						['type' => 'Check for error in JSON', 'parameter_1' => ''],
						['type' => 'Check for error in XML', 'parameter_1' => ''],
						['type' => 'Check for error using regular expression', 'parameter_1' => ''],
						['type' => 'Discard unchanged with heartbeat', 'parameter_1' => ''],
						['type' => 'Prometheus pattern', 'parameter_1' => '', 'parameter_2' => ''],
					],
					'error' => 'Incorrect value for field "params":'
				]
			]
		];
	}

	/**
	 * @dataProvider getTestData
	 */
	public function testFormItemPreprocessingTest_Test($data) {
		$this->page->login()->open('items.php?filter_set=1&filter_hostids[0]='.self::HOST_ID);
		$this->query('button:Create item')->waitUntilPresent()->one()->click();

		$form = $this->query('name:itemForm')->waitUntilPresent()->asForm()->one();
		$form->fill($data['fields']);
		$form->selectTab('Preprocessing');
		foreach ($data['preprocessing'] as $i => $step) {
			$this->addPreprocessingSteps([$step]);
			$this->query('name:preprocessing['.$i.'][test]')->waitUntilPresent()->one()->click();
			$dialog = $this->query('id:overlay_dialogue')->waitUntilPresent()->asOverlayDialog()->one()->waitUntilReady();


			switch ($data['expected']) {
				case TEST_BAD:
					$message = $dialog->query('tag:output')->waitUntilPresent()->asMessage()->one();
					$this->assertTrue($message->isBad());
					// Workaround for single step which has different message (ask Valdis).
					if ($step['type']=== 'Discard unchanged with heartbeat'){
						$this->assertTrue($message->hasLine('Invalid parameter "params":'));
					}
					else {
					$this->assertTrue($message->hasLine($data['error']));
					}
					break;
				case TEST_GOOD:
					$form = $this->query('id:preprocessing-test-form')->waitUntilPresent()->asForm()->one();
					$header = $dialog->query('id:dashbrd-widget-head-title-preprocessing-test')->waitUntilPresent()->one();
					$this->assertEquals('Test item preprocessing', $header->getText());

					$time = $dialog->query('id:time')->one();
					$this->assertTrue($time->getAttribute('readonly') !== null);

					$types = [
						'Discard unchanged with heartbeat',
						'Simple change',
						'Change per second',
						'Discard unchanged'
					];

					$prev_value = $dialog->query('id:prev_value')->asMultiline()->one();
					$this->assertTrue($prev_value->isEnabled(in_array($step['type'], $types)));

					$prev_time = $dialog->query('id:prev_time')->one();
					$this->assertTrue($prev_time->isEnabled(in_array($step['type'], $types)));

					$radio = $form->getField('End of line sequence');
					$this->assertTrue($radio->isEnabled());

					$table = $form->getField('Preprocessing steps')->asTable();
					$this->assertEquals('1: '.$step['type'], $table->getRow(0)->getText());
					break;
			}
			$dialog->close();
		}
	}

	public static function getTestAllStepsData() {
		return [
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Item Preprocessing Test',
						'Key' => 'item-preprocessing-test'
					],
					'preprocessing' => [
						['type' => 'Regular expression', 'parameter_1' => 'expression', 'parameter_2' => 'test output'],
						['type' => 'Trim', 'parameter_1' => '1a2b3c'],
						['type' => 'Right trim', 'parameter_1' => 'abc'],
						['type' => 'Left trim', 'parameter_1' => 'def'],
						['type' => 'XML XPath', 'parameter_1' => 'path'],
						['type' => 'JSONPath', 'parameter_1' => 'path'],
						['type' => 'Custom multiplier', 'parameter_1' => '123'],
						['type' => 'Change per second'],
						['type' => 'Boolean to decimal'],
						['type' => 'Octal to decimal'],
						['type' => 'Hexadecimal to decimal'],
						['type' => 'JavaScript', 'parameter_1' => 'Test JavaScript'],
						['type' => 'In range', 'parameter_1' => '-5', 'parameter_2' => '9.5'],
						['type' => 'Matches regular expression', 'parameter_1' => 'expression'],
						['type' => 'Does not match regular expression', 'parameter_1' => 'expression'],
						['type' => 'Check for error in JSON', 'parameter_1' => 'path'],
						['type' => 'Check for error in XML', 'parameter_1' => 'path'],
						['type' => 'Check for error using regular expression', 'parameter_1' => 'path', 'parameter_2' => 'output'],
						['type' => 'Discard unchanged with heartbeat', 'parameter_1' => '1'],
						['type' => 'Prometheus to JSON', 'parameter_1' => '']
					]
				]
			],
//			[
//				[
//					'expected' => TEST_BAD,
//					'fields' => [
//						'Name' => 'Item Preprocessing Test Repeated steps',
//						'Key' => 'item-preprocessing-test-repeated'
//					],
//					'preprocessing' => [
//						['type' => 'Simple change'],
//						['type' => 'Change per second'],
//						['type' => 'Discard unchanged'],
//						['type' => 'Discard unchanged with heartbeat', 'parameter_1' => '1'],
//						['type' => 'Prometheus pattern', 'parameter_1' => 'cpu_usage_system', 'parameter_2' => 'label_name'],
//						['type' => 'Prometheus to JSON', 'parameter_1' => '']
//					],
//					'error' => 'Only one change step is allowed.'
//				]
//			],
//			//			[
//				[
//					'expected' => TEST_BAD,
//					'fields' => [
//						'Name' => 'Item Preprocessing Test Repeated steps',
//						'Key' => 'item-preprocessing-test-repeated'
//					],
//					'preprocessing' => [
//						['type' => 'Discard unchanged'],
//						['type' => 'Discard unchanged with heartbeat', 'parameter_1' => '1'],
//						['type' => 'Prometheus pattern', 'parameter_1' => 'cpu_usage_system', 'parameter_2' => 'label_name'],
//						['type' => 'Prometheus to JSON', 'parameter_1' => '']
//					],
//					'error' => '    Only one throttling step is allowed.'
//				]
//			],
//			[
//				[
//					'expected' => TEST_BAD,
//					'fields' => [
//						'Name' => 'Item Preprocessing Test Validation',
//						'Key' => 'item-preprocessing-test-validation'
//					],
//					'preprocessing' => [
//						['type' => 'Regular expression', 'parameter_1' => '', 'parameter_2' => ''],
//						['type' => 'Trim', 'parameter_1' => ''],
//						['type' => 'Right trim', 'parameter_1' => ''],
//						['type' => 'Left trim', 'parameter_1' => ''],
//						['type' => 'XML XPath', 'parameter_1' => ''],
//						['type' => 'JSONPath', 'parameter_1' => ''],
//						['type' => 'Custom multiplier', 'parameter_1' => ''],
//						['type' => 'JavaScript', 'parameter_1' => ''],
//						['type' => 'In range', 'parameter_1' => '', 'parameter_2' => ''],
//						['type' => 'Matches regular expression', 'parameter_1' => ''],
//						['type' => 'Does not match regular expression', 'parameter_1' => ''],
//						['type' => 'Check for error in JSON', 'parameter_1' => ''],
//						['type' => 'Check for error in XML', 'parameter_1' => ''],
//						['type' => 'Check for error using regular expression', 'parameter_1' => ''],
//						['type' => 'Discard unchanged with heartbeat', 'parameter_1' => ''],
//						['type' => 'Prometheus pattern', 'parameter_1' => '', 'parameter_2' => ''],
//					],
//					'error' => 'Incorrect value for field "params":'
//				]
//			]
		];
	}

	/**
	 * @dataProvider getTestAllStepsData
	 */
	public function testFormItemPreprocessingTest_TestAllSteps($data) {
		$this->page->login()->open('items.php?filter_set=1&filter_hostids[0]='.self::HOST_ID);
		$this->query('button:Create item')->waitUntilPresent()->one()->click();

		$form = $this->query('name:itemForm')->waitUntilPresent()->asForm()->one();
		$form->fill($data['fields']);
		$form->selectTab('Preprocessing');
		foreach ($data['preprocessing'] as $i => $step) {
			$this->addPreprocessingSteps([$step]);
		}
		$this->query('button:Test all steps')->waitUntilPresent()->one()->click();
		$dialog = $this->query('id:overlay_dialogue')->waitUntilPresent()->asOverlayDialog()->one()->waitUntilReady();

		switch ($data['expected']) {
				case TEST_BAD:
					$message = $dialog->query('tag:output')->waitUntilPresent()->asMessage()->one();
					$this->assertTrue($message->isBad());
					$this->assertTrue($message->hasLine($data['error']));
					break;
				case TEST_GOOD:
					$form = $this->query('id:preprocessing-test-form')->waitUntilPresent()->asForm()->one();
					$header = $dialog->query('id:dashbrd-widget-head-title-preprocessing-test')->waitUntilPresent()->one();
					$this->assertEquals('Test item preprocessing', $header->getText());

					$time = $dialog->query('id:time')->one();
					$this->assertTrue($time->getAttribute('readonly') !== null);

					$prev_value = $dialog->query('id:prev_value')->asMultiline()->one();
					$this->assertTrue($prev_value->isEnabled());

					$prev_time = $dialog->query('id:prev_time')->one();
					$this->assertTrue($prev_time->isEnabled());

					$radio = $form->getField('End of line sequence');
					$this->assertTrue($radio->isEnabled());

					$table = $form->getField('Preprocessing steps')->asTable();
					foreach ($data['preprocessing'] as $i => $step) {
						$this->assertEquals(($i+1).': '.$step['type'], $table->getRow($i)->getText());
					}
					break;
		}
	}
}
