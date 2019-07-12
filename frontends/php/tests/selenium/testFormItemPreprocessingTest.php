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

	public $chage_types = [
				'Discard unchanged with heartbeat',
				'Simple change',
				'Change per second',
				'Discard unchanged'
			];

	public static function getTestSingleStepData() {
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
					],
					'action' => 'Test'
				]
			],
						[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Item Preprocessing Test success empty parameters',
						'Key' => 'item-preprocessing-test-success-empty-params'
					],
					'preprocessing' => [
						['type' => 'In range', 'parameter_1' => '1', 'parameter_2' => ''],
						['type' => 'In range', 'parameter_1' => '', 'parameter_2' => '2'],
						['type' => 'Prometheus pattern', 'parameter_1' => 'cpu', 'parameter_2' => '']
					],
					'action' => 'Cancel'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Preprocessing Test empty parameters Validation',
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
						['type' => 'Check for error using regular expression', 'parameter_1' => '', 'parameter_2' => ''],
						['type' => 'Discard unchanged with heartbeat', 'parameter_1' => ''],
						['type' => 'Prometheus pattern', 'parameter_1' => '', 'parameter_2' => '']
					],
					'error' => 'Incorrect value for field "params":'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Preprocessing Test Validation empty second parameter',
						'Key' => 'item-preprocessing-test-validation-second-parameter'
					],
					'preprocessing' => [
						['type' => 'Regular expression', 'parameter_1' => '1', 'parameter_2' => ''],
						['type' => 'Check for error using regular expression', 'parameter_1' => 'path', 'parameter_2' => '']

					],
					'error' => 'Incorrect value for field "params": second parameter is expected.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Preprocessing Test Validation empty first parameter',
						'Key' => 'item-preprocessing-test-validation-first-parameter'
					],
					'preprocessing' => [
						['type' => 'Regular expression', 'parameter_1' => '', 'parameter_2' => '1'],
						['type' => 'Prometheus pattern', 'parameter_1' => '', 'parameter_2' => 'label']
					],
					'error' => 'Incorrect value for field "params": first parameter is expected.'
				]
			]
		];
	}

	/**
	 * @dataProvider getTestSingleStepData
	 */
	public function testFormItemPreprocessingTest_TestSingleStep($data) {
		$this->openPreprocessing($data);

		foreach ($data['preprocessing'] as $i => $step) {
			$this->addPreprocessingSteps([$step]);

			$button = 'name:preprocessing['.$i.'][test]';
			$expression = in_array($step['type'], $this->chage_types);
			$this->checkTestOverlay($data, $button, 'TestSingleStep', $step, $expression);
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
						['type' => 'Trim', 'parameter_1' => '1a2b3c']
					],
					'action' => 'Cancel'
				]
			],
						[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Item Preprocessing Test no previous value',
						'Key' => 'item-preprocessing-test-no-prev-value'
					],
					'preprocessing' => [
						['type' => 'Right trim', 'parameter_1' => 'abc'],
						['type' => 'Left trim', 'parameter_1' => 'def']
					],
					'action' => 'Test'
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Item Preprocessing Test with previous value 1',
						'Key' => 'item-preprocessing-test-prev-value-1'
					],
					'preprocessing' => [
						['type' => 'Simple change'],
						['type' => 'Discard unchanged'],
						['type' => 'In range', 'parameter_1' => '1', 'parameter_2' => ''],
					],
					'action' => 'Test'
				]
			],
						[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Item Preprocessing Test with previous value 2',
						'Key' => 'item-preprocessing-test-prev-value-2'
					],
					'preprocessing' => [
						['type' => 'Discard unchanged with heartbeat', 'parameter_1' => '1'],
						['type' => 'Change per second']
					],
					'action' => 'Test'
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Item Preprocessing Test success empty parameters',
						'Key' => 'item-preprocessing-test-success-empty-params'
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => ''],
						['type' => 'In range', 'parameter_1' => '1', 'parameter_2' => ''],
						['type' => 'In range', 'parameter_1' => '', 'parameter_2' => '2']
					],
					'action' => 'Close'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Preprocessing Test Repeated Change steps',
						'Key' => 'item-preprocessing-test-repeated-change'
					],
					'preprocessing' => [
						['type' => 'Simple change'],
						['type' => 'Change per second']
					],
					'error' => 'Only one change step is allowed.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Preprocessing Test Repeated Throttling steps',
						'Key' => 'item-preprocessing-test-repeated-throttling'
					],
					'preprocessing' => [
						['type' => 'Discard unchanged'],
						['type' => 'Discard unchanged with heartbeat', 'parameter_1' => '1']
					],
					'error' => 'Only one throttling step is allowed.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Preprocessing Test Repeated Prometheus steps',
						'Key' => 'item-preprocessing-test-repeated-throttling'
					],
					'preprocessing' => [
						['type' => 'Prometheus pattern', 'parameter_1' => 'cpu_usage_system', 'parameter_2' => 'label_name'],
						['type' => 'Prometheus to JSON', 'parameter_1' => '']
					],
					'error' => 'Only one Prometheus step is allowed.'
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
						['type' => 'Check for error using regular expression', 'parameter_1' => '', 'parameter_2' => ''],
						['type' => 'Discard unchanged with heartbeat', 'parameter_1' => ''],
						['type' => 'Prometheus pattern', 'parameter_1' => '', 'parameter_2' => '']
					],
					'error' => 'Incorrect value for field "params":'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Preprocessing Test Validation Empty second step parameter',
						'Key' => 'item-preprocessing-test-validation'
					],
					'preprocessing' => [
						['type' => 'Regular expression', 'parameter_1' => 'expr', 'parameter_2' => 'output'],
						['type' => 'Trim', 'parameter_1' => '']
					],
					'error' => 'Incorrect value for field "params":'
				]
			],
						[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Preprocessing Test Validation Empty fifth step parameter',
						'Key' => 'item-preprocessing-test-validation'
					],
					'preprocessing' => [
						['type' => 'Right trim', 'parameter_1' => '1'],
						['type' => 'Left trim', 'parameter_1' => '2'],
						['type' => 'Custom multiplier', 'parameter_1' => '10'],
						['type' => 'JavaScript', 'parameter_1' => 'Script'],
						['type' => 'Check for error in XML', 'parameter_1' => '']
					],
					'error' => 'Incorrect value for field "params":'
				]
			]
		];
	}

	/**
	 * @dataProvider getTestAllStepsData
	 */
	public function testFormItemPreprocessingTest_TestAllSteps($data) {
		$this->openPreprocessing($data);

		foreach ($data['preprocessing'] as $step) {
			$this->addPreprocessingSteps([$step]);
		}

		$button = 'button:Test all steps';
		$expression = $this->hasChangeSteps($data);
		$this->checkTestOverlay($data, $button, 'TestAllSteps', $step, $expression);
	}

	private function openPreprocessing($data) {
		$this->page->login()->open('items.php?filter_set=1&filter_hostids[0]='.self::HOST_ID);
		$this->query('button:Create item')->waitUntilPresent()->one()->click();
		$form = $this->query('name:itemForm')->waitUntilPresent()->asForm()->one();
		$form->fill($data['fields']);
		$form->selectTab('Preprocessing');
	}

	private function checkTestOverlay($data, $button, $case, $step, $expression) {
		$this->query($button)->waitUntilPresent()->one()->click();
		$dialog = $this->query('id:overlay_dialogue')->waitUntilPresent()->asOverlayDialog()->one()->waitUntilReady();

		switch ($data['expected']) {
			case TEST_BAD:
				$message = $dialog->query('tag:output')->waitUntilPresent()->asMessage()->one();
				$this->assertTrue($message->isBad());

				// Workaround for single step which has different message.
				if ($step['type']=== 'Discard unchanged with heartbeat' && $case === 'TestSingleStep'){
					$this->assertTrue($message->hasLine('Invalid parameter "params":'));
				}
				else {
					$this->assertTrue($message->hasLine($data['error']));
				}
				$dialog->close();
				break;

			case TEST_GOOD:
				$form = $this->query('id:preprocessing-test-form')->waitUntilPresent()->asForm()->one();
				$this->assertEquals('Test item preprocessing', $dialog->getTitle());

				$time = $dialog->query('id:time')->one();
				$this->assertTrue($time->getAttribute('readonly') !== null);

				$prev_value = $dialog->query('id:prev_value')->asMultiline()->one();
				$prev_time = $dialog->query('id:prev_time')->one();

				$this->assertTrue($prev_value->isEnabled($expression));
				$this->assertTrue($prev_time->isEnabled($expression));

				$radio = $form->getField('End of line sequence');
				$this->assertTrue($radio->isEnabled());

				$table = $form->getField('Preprocessing steps')->asTable();

				switch ($case) {
					case 'TestSingleStep':
						$this->assertEquals('1: '.$step['type'], $table->getRow(0)->getText());
						break;
					case 'TestAllSteps':
						foreach ($data['preprocessing'] as $i => $step) {
							$this->assertEquals(($i+1).': '.$step['type'], $table->getRow($i)->getText());
						}
						break;
				}

				$this->chooseDialogActions($data);
				break;
		}
	}

	/**
	 * Check if preprocessing steps contain Change or Throttling values.
	 */
	private function hasChangeSteps($data){
		foreach ($data['preprocessing'] as $step) {
			if (in_array($step['type'], $this->chage_types)) {
				return true;
				break;
			}
		}
		return false;
	}

	private function chooseDialogActions($data){
		$dialog = $this->query('id:overlay_dialogue')->waitUntilPresent()->asOverlayDialog()->one()->waitUntilReady();
		$form = $this->query('id:preprocessing-test-form')->waitUntilPresent()->asForm()->one();
		switch ($data['action']) {
			case 'Test':
				$value_string = '123';
				$prev_value_string = '100';
				$prev_time_string  = 'now-1s';

				$container_current = $form->getFieldContainer('Value');
				$container_current->query('id:value')->asMultiline()->one()->fill($value_string);

				$container_prev = $form->getFieldContainer('Previous value');
				$prev_value = $container_prev->query('id:prev_value')->asMultiline()->one();
				$prev_time = $container_prev->query('id:prev_time')->one();

				if ($prev_value->isEnabled(true) && $prev_time->isEnabled(true)) {
					$prev_value->fill($prev_value_string);
					$prev_time->fill($prev_time_string);
				}
				$form->getField('End of line sequence')->fill('CRLF');
				$form->submit();

				// Check Zabbix server down message.
				$message = $dialog->query('tag:output')->waitUntilPresent()->asMessage()->one();
				$this->assertTrue($message->isBad());
				$this->assertTrue($message->hasLine('Connection to Zabbix server "localhost" refused. Possible reasons:'));
				$dialog->close();
				break;

			case 'Cancel':
				$dialog->query('button:Cancel')->one()->click();
				$dialog->waitUntilNotPresent();
				break;

			default:
				$dialog->close();
		}
	}
}
