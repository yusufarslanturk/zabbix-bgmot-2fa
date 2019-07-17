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
 * Base class for Preprocessing Inheritance tests.
 */
class TestInheritancePreprocessing extends CWebTest {

	use PreprocessingTrait;

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

