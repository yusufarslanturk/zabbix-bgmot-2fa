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
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

require_once dirname(__FILE__).'/TestInheritancePreprocessing.php';

/**
 * Test the creation of inheritance of new objects on a previously linked template.
 *
 * @backup items
 */
class testInheritanceItemPrototypePreprocessing extends TestInheritancePreprocessing {

	const TEMPL_DISCOVERY_RULE_ID = 15011;	// 'testInheritanceDiscoveryRule'
	const HOST_DISCOVERY_RULE_ID	 = 15016;	// 'Template inheritance test host -> testInheritanceDiscoveryRule'

	/**
	 * Data provider for Preprocessing inheritance test.
	 *
	 * @return array
	 */
	public function getPreprocessingData() {
		return [
			[
				[
					[
						'type' => 'Regular expression',
						'parameter_1' => 'expression',
						'parameter_2' => '\1',
						'on_fail' => true,
						'error_handler' => 'Discard value'
					],
					[
						'type' => 'JSONPath',
						'parameter_1' => '$.data.test',
						'on_fail' => true,
						'error_handler' => 'Set value to',
						'error_handler_params' => 'Custom_text'
					],
					[
						'type' => 'Does not match regular expression',
						'parameter_1' => 'Pattern',
						'on_fail' => true,
						'error_handler' => 'Set error to',
						'error_handler_params' => 'Custom_text'
					],
					[
						'type' => 'Check for error in JSON',
						'parameter_1' => '$.new.path'
					],
					[
						'type' => 'Discard unchanged with heartbeat',
						'parameter_1' => '30'
					],
					[
						'type' => 'Right trim',
						'parameter_1' => '5'
					],
					[
						'type' => 'Custom multiplier',
						'parameter_1' => '10',
						'on_fail' => false
					],
					[
						'type' => 'Simple change',
						'on_fail' => false
					],
					[
						'type' => 'Octal to decimal',
						'on_fail' => true,
						'error_handler' => 'Set error to',
						'error_handler_params' => 'Custom_text'
					],
//					[
//						'type' => 'JavaScript',
//						'parameter_1' => "  Test line 1\n  Test line 2\nTest line 3  "
//					],
					[
						'type' => 'Check for error using regular expression',
						'parameter_1' => 'expression',
						'parameter_2' => '\0'
					],
					[
						'type' => 'Prometheus pattern',
						'parameter_1' => 'cpu_usage_system',
						'parameter_2' => 'label_name',
						'on_fail' => true,
						'error_handler' => 'Set error to',
						'error_handler_params' => 'Custom_text'
					]
				]
			]
		];
	}

	/**
	 * @dataProvider getPreprocessingData
	 */
	public function testInheritanceItemPrototypePreprocessing_PreprocessingInheritanceFromTemplate($preprocessing) {
		$fields = [
			'Name' => 'Templated item prototype with Preprocessing steps',
			'Key' => 'templated-item-proto-with-preprocessing-steps'
		];

		$link = 'disc_prototypes.php?parent_discoveryid='.self::TEMPL_DISCOVERY_RULE_ID;
		$ready_link = 'disc_prototypes.php?parent_discoveryid='.self::HOST_DISCOVERY_RULE_ID;
		$selector = 'Create item prototype';
		$success_message = 'Item prototype added';

		$this->executeTestInheritance($preprocessing, $link, $selector, $fields, $success_message, $ready_link);
	}
}
