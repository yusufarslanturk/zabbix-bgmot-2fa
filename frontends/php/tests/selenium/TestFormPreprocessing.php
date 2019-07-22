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

require_once dirname(__FILE__).'/../include/CWebTest.php';
require_once dirname(__FILE__).'/../../include/items.inc.php';
require_once dirname(__FILE__).'/traits/PreprocessingTrait.php';

/**
 * Base class for Preprocessing tests.
 */
class TestFormPreprocessing extends CWebTest {

	use PreprocessingTrait;

	/**
	 * Check creating items or LLD rules with preprocessing steps.
	 */
	protected function executeCreate($data, $link, $selector, $success_message, $ready_link, $fail_message) {
		if ($data['expected'] === TEST_BAD) {
			$sql_items = 'SELECT * FROM items ORDER BY itemid';
			$old_hash = CDBHelper::getHash($sql_items);
		}
		$this->page->login()->open($link);
		$this->query('button:'.$selector)->waitUntilPresent()->one()->click();

		$form = $this->query('name:itemForm')->waitUntilPresent()->asForm()->one();
		$form->fill($data['fields']);
		$form->selectTab('Preprocessing');
		$this->addPreprocessingSteps($data['preprocessing']);
		$form->submit();

		$this->page->waitUntilReady();
		$message = CMessageElement::find()->one();

		switch ($data['expected']) {
			case TEST_GOOD:
				$this->assertTrue($message->isGood());
				$this->assertEquals($success_message, $message->getTitle());

				// Check result in frontend form.
				$id = CDBHelper::getValue('SELECT itemid FROM items WHERE key_='.zbx_dbstr($data['fields']['Key']));
				$this->page->open($ready_link.$id);
				$form->selectTab('Preprocessing');
				// Check for Trailing spaces case.
				if ($data['fields']['Name'] === 'Trailing spaces'){
					$data['preprocessing'][0]['parameter_1'] = trim($data['preprocessing'][0]['parameter_1']);
					if (array_key_exists('parameter_2', $data['preprocessing'][0])){
						$data['preprocessing'][0]['parameter_2'] = trim($data['preprocessing'][0]['parameter_2']);
					}
				}
				$this->assertPreprocessingSteps($data['preprocessing']);
				break;

			case TEST_BAD:
				$this->assertTrue($message->isBad());
				$this->assertEquals($fail_message, $message->getTitle());
				$this->assertTrue($message->hasLine($data['error']));
				// Check that DB hash is not changed.
				$this->assertEquals($old_hash, CDBHelper::getHash($sql_items));
				break;
		}
	}

	public static function getCustomOnFailData() {
		$options = [
			ZBX_PREPROC_FAIL_DISCARD_VALUE	=> 'Discard value',
			ZBX_PREPROC_FAIL_SET_VALUE		=> 'Set value to',
			ZBX_PREPROC_FAIL_SET_ERROR		=> 'Set error to'
		];

		$data = [];
		foreach ($options as $value => $label) {
			$item = [
				'fields' => [
					'Name' => 'LLD Preprocessing '.$label,
					'Key' => 'lld-preprocessing-steps-discard-on-fail'.$value
				],
				'preprocessing' => [
					[
						'type' => 'Regular expression',
						'parameter_1' => 'expression',
						'parameter_2' => '\1',
						'on_fail' => true
					],
					[
						'type' => 'JSONPath',
						'parameter_1' => '$.data.test',
						'on_fail' => true
					],
					[
						'type' => 'Does not match regular expression',
						'parameter_1' => 'Pattern',
						'on_fail' => true
					],
					[
						'type' => 'Check for error in JSON',
						'parameter_1' => '$.new.path'
					],
					[
						'type' => 'Discard unchanged with heartbeat',
						'parameter_1' => '30'
					]
				],
				'value' => $value
			];

			foreach ($item['preprocessing'] as &$step) {
				if (!array_key_exists('on_fail', $step) || !$step['on_fail']) {
					continue;
				}

				$step['error_handler'] = $label;

				if ($value !== ZBX_PREPROC_FAIL_DISCARD_VALUE) {
					$step['error_handler_params'] = 'handler parameter';
				}
			}

			$data[] = [$item];
		}

		return $data;
	}

	/**
	 * Check Custom on fail checkbox.
	 */
	public function executeCustomOnFail($data, $link, $selector, $success_message, $ready_link) {
		$this->page->login()->open($link);
		$this->query('button:'.$selector)->one()->click();

		$form = $this->query('name:itemForm')->asForm()->one();
		$form->fill($data['fields']);

		$form->selectTab('Preprocessing');
		$this->addPreprocessingSteps($data['preprocessing']);
		$steps = $this->getPreprocessingSteps();

		foreach ($data['preprocessing'] as $i => $options) {
			if ($options['type'] === 'Check for error in JSON'
					|| $options['type'] === 'Discard unchanged with heartbeat') {

				$this->assertFalse($steps[$i]['on_fail']->isEnabled());
			}
		}

		$form->submit();
		$this->page->waitUntilReady();

		// Check message title and if message is positive.
		$message = CMessageElement::find()->one();
		$this->assertTrue($message->isGood());
		$this->assertEquals($success_message, $message->getTitle());

		// Get item data from DB.
		$db_item = CDBHelper::getRow('SELECT name,key_,itemid FROM items where key_='.
				zbx_dbstr($data['fields']['Key'])
		);
		$this->assertEquals($db_item['name'], $data['fields']['Name']);
		$itemid = $db_item['itemid'];

		// Check saved pre-processing.
		$this->page->open($ready_link.$itemid);
		$form->selectTab('Preprocessing');
		$steps = $this->assertPreprocessingSteps($data['preprocessing']);

		$rows = [];
		foreach (CDBHelper::getAll('SELECT step, error_handler FROM item_preproc WHERE itemid='.$itemid) as $row) {
			$rows[$row['step']] = $row['error_handler'];
		}

		foreach ($data['preprocessing'] as $i => $options) {
			// Validate preprocessing step in DB.
			$expected = (!array_key_exists('on_fail', $options) || !$options['on_fail'])
					? ZBX_PREPROC_FAIL_DEFAULT : $data['value'];

			$this->assertEquals($expected, $rows[$i + 1]);

			if (in_array($options['type'], ['Check for error in JSON', 'Discard unchanged with heartbeat'])){
				$this->assertFalse($steps[$i]['on_fail']->isEnabled());
				$this->assertFalse($steps[$i]['on_fail']->isSelected());
				$this->assertTrue($steps[$i]['error_handler'] === null || !$steps[$i]['error_handler']->isVisible());
				$this->assertTrue($steps[$i]['error_handler_params'] === null
					|| !$steps[$i]['error_handler_params']->isVisible()
				);
			}
			else {
				$this->assertTrue($steps[$i]['on_fail']->isSelected());
				$this->assertTrue($steps[$i]['on_fail']->isEnabled());
			}
		}
	}

	public static function getCustomOnFailValidationData() {
		$cases = [
			// 'Set value to' validation.
			[
				'expected' => TEST_GOOD,
				'fields' => [
					'Name' => 'Set value empty',
					'Key' => 'set-value-empty'
				],
				'custom_on_fail' => [
					'error_handler' => 'Set value to',
					'error_handler_params' => ''
				]
			],
			[
				'expected' => TEST_GOOD,
				'fields' => [
					'Name' => 'Set value number',
					'Key' => 'set-value-number'
				],
				'custom_on_fail' => [
					'error_handler' => 'Set value to',
					'error_handler_params' => '500'
				]
			],
			[
				'expected' => TEST_GOOD,
				'fields' => [
					'Name' => 'Set value string',
					'Key' => 'set-value-string'
				],
				'custom_on_fail' => [
					'error_handler' => 'Set error to',
					'error_handler_params' => 'String'
				]
			],
			[
				'expected' => TEST_GOOD,
				'fields' => [
					'Name' => 'Set value special-symbols',
					'Key' => 'set-value-special-symbols'
				],
				'custom_on_fail' => [
					'error_handler' => 'Set value to',
					'error_handler_params' => '!@#$%^&*()_+<>,.\/'
				]
			],
			// 'Set error to' validation.
			[
				'expected' => TEST_BAD,
				'fields' => [
					'Name' => 'Set error empty',
					'Key' => 'set-error-empty'
				],
				'custom_on_fail' => [
					'error_handler' => 'Set error to',
					'error_handler_params' => ''
				],
				'error_details' => 'Incorrect value for field "error_handler_params": cannot be empty.'
			],
			[
				'expected' => TEST_GOOD,
				'fields' => [
					'Name' => 'Set error string',
					'Key' => 'set-error-string'
				],
				'custom_on_fail' => [
					'error_handler' => 'Set error to',
					'error_handler_params' => 'Test error'
				]
			],
			[
				'expected' => TEST_GOOD,
				'fields' => [
					'Name' => 'Set error number',
					'Key' => 'set-error-number'
				],
				'custom_on_fail' => [
					'error_handler' => 'Set error to',
					'error_handler_params' => '999'
				]
			],
			[
				'expected' => TEST_GOOD,
				'fields' => [
					'Name' => 'Set error special symbols',
					'Key' => 'set-error-special-symbols'
				],
				'custom_on_fail' => [
					'error_handler' => 'Set error to',
					'error_handler_params' => '!@#$%^&*()_+<>,.\/'
				]
			]
		];

		$data = [];
		$preprocessing = [
			[
				'type' => 'Regular expression',
				'parameter_1' => 'expression',
				'parameter_2' => '\1',
				'on_fail' => true
			],
			[
				'type' => 'JSONPath',
				'parameter_1' => '$.data.test',
				'on_fail' => true
			],
			[
				'type' => 'Does not match regular expression',
				'parameter_1' => 'Pattern',
				'on_fail' => true
			]
		];

		foreach ($cases as $case) {
			$case['preprocessing'] = [];
			foreach ($preprocessing as $step) {
				$case['preprocessing'][] = array_merge($step, $case['custom_on_fail']);
			}

			$data[] = [$case];
		}

		return $data;
	}

	/**
	 * Check Custom on fail validation.
	 */
	public function executeCustomOnFailValidation($data, $link, $selector, $success_message, $fail_message) {
		$this->page->login()->open($link);
		$this->query('button:'.$selector)->one()->click();

		$form = $this->query('name:itemForm')->asForm()->one();
		$form->fill($data['fields']);
		$form->selectTab('Preprocessing');
		$this->addPreprocessingSteps($data['preprocessing']);
		$form->submit();
		$this->page->waitUntilReady();

		// Get global message.
		$message = CMessageElement::find()->one();

		switch ($data['expected']) {
			case TEST_GOOD:
				// Check if message is positive.
				$this->assertTrue($message->isGood());
				// Check message title.
				$this->assertEquals($success_message, $message->getTitle());
				// Check the results in DB.
				$this->assertEquals(1, CDBHelper::getCount('SELECT NULL FROM items WHERE key_='.
						zbx_dbstr($data['fields']['Key']))
				);
				break;

			case TEST_BAD:
				// Check if message is negative.
				$this->assertTrue($message->isBad());
				// Check message title.
				$this->assertEquals($fail_message, $message->getTitle());
				$this->assertTrue($message->hasLine($data['error_details']));
				// Check that DB.
				$this->assertEquals(0, CDBHelper::getCount('SELECT * FROM items where key_ = '.
						zbx_dbstr($data['fields']['Key']))
				);
				break;
		}
	}

	/**
	 * Check inheritance of preprocessing steps in items or LLD rules.
	 */
	protected function executeTestInheritance($preprocessing, $link, $selector, $fields, $success_message, $ready_link) {
		// Create item on template.
		$this->page->login()->open($link);
		$this->query('button:'.$selector)->waitUntilPresent()->one()->click();

		$form = $this->query('name:itemForm')->waitUntilPresent()->asForm()->one();
		$form->fill($fields);

		$form->selectTab('Preprocessing');
		$this->addPreprocessingSteps($preprocessing);
		$form->submit();
		$this->page->waitUntilReady();

		$message = CMessageElement::find()->one();
		$this->assertTrue($message->isGood());
		$this->assertEquals($success_message, $message->getTitle());

		// Check preprocessing steps on host.
		$this->page->open($ready_link);
		$this->query('link:'.$fields['Name'])->one()->click();
		$this->page->waitUntilReady();

		$form->selectTab('Preprocessing');
		$steps = $this->assertPreprocessingSteps($preprocessing);

		foreach ($preprocessing as $i => $options) {
			$step = $steps[$i];
			$this->assertNotNull($step['type']->getAttribute('readonly'));

			foreach (['parameter_1', 'parameter_2'] as $param) {
				if (array_key_exists($param, $options)) {
					$this->assertNotNull($step[$param]->getAttribute('readonly'));
				}
			}

			$this->assertNotNull($step['on_fail']->getAttribute('disabled'));

			switch ($options['type']) {
				case 'Regular expression':
				case 'JSONPath':
				case 'Does not match regular expression':
				case 'Octal to decimal':
				case 'Prometheus pattern':
					$this->assertTrue($step['on_fail']->isSelected());
					$this->assertFalse($step['error_handler']->isEnabled());
					break;
				case 'Custom multiplier':
				case 'Simple change':
				case 'Right trim':
				case 'JavaScript':
				case 'Check for error in JSON':
				case 'Check for error using regular expression':
				case 'Discard unchanged with heartbeat':
					$this->assertFalse($step['on_fail']->isSelected());
					break;
			}
		}
	}
}
