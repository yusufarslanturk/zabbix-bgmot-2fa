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

require_once dirname(__FILE__).'/TestFormPreprocessing.php';

/**
 * @backup items
 */
class testFormLowLevelDiscoveryPreprocessing extends TestFormPreprocessing {

	const HOST_ID = 40001;
	const INHERITANCE_TEMPLATE_ID	= 15000;		// 'Inheritance test template'
	const INHERITANCE_HOST_ID		= 15001;		// 'Template inheritance test host'

	public $link = 'host_discovery.php?hostid='.self::HOST_ID;
	public $ready_link = 'host_discovery.php?form=update&itemid=';
	public $selector = 'Create discovery rule';
	public $success_message = 'Discovery rule created';
	public $fail_message = 'Cannot add discovery rule';

	public static function getCreateAllStepsData() {
		return [
			[
				// Validation. Regular expression.
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'LLD empty regular expression',
						'Key' => 'lld-empty-both-parameters',
					],
					'preprocessing' => [
						['type' => 'Regular expression']
					],
					'error' => 'Incorrect value for field "params": first parameter is expected.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'LLD empty pattern of regular expression',
						'Key' => 'lld-empty-first-parameter',
					],
					'preprocessing' => [
						['type' => 'Regular expression', 'parameter_2' => 'test output']
					],
					'error' => 'Incorrect value for field "params": first parameter is expected.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'LLD empty output of regular expression',
						'Key' => 'lld-empty-second-parameter',
					],
					'preprocessing' => [
						['type' => 'Regular expression', 'parameter_1' => 'expression'],
					],
					'error' => 'Incorrect value for field "params": second parameter is expected.'
				]
			],
			// Validation. JSONPath.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'LLD JSONPath empty',
						'Key' => 'lld-empty-jsonpath'
					],
					'preprocessing' => [
						['type' => 'JSONPath']
					],
					'error' => 'Incorrect value for field "params": cannot be empty.'
				]
			],
			// Custom scripts. JavaScript.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item empty JavaScript',
						'Key' => 'item-empty-javascript',
					],
					'preprocessing' => [
						['type' => 'JavaScript']
					],
					'error' => 'Incorrect value for field "params": cannot be empty.'
				]
			],
			// Validation. Regular expressions matching.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'LLD Does not match regular expression empty',
						'Key' => 'lld-does-not-match-regular-expression-empty',
					],
					'preprocessing' => [
						['type' => 'Does not match regular expression']
					],
					'error' => 'Incorrect value for field "params": cannot be empty.'
				]
			],
			// Validation. Error in JSON.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'LLD error JSON empty',
						'Key' => 'lld-error-json-empty'
					],
					'preprocessing' => [
						['type' => 'Check for error in JSON']
					],
					'error' => 'Incorrect value for field "params": cannot be empty.'
				]
			],
			// Validation. Throttling.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'LLD two equal discard unchanged with heartbeat',
						'Key' => 'lld-two-equal-discard-uncahnged-with-heartbeat'
					],
					'preprocessing' => [
						['type' => 'Discard unchanged with heartbeat', 'parameter_1' => '1'],
						['type' => 'Discard unchanged with heartbeat', 'parameter_1' => '1']
					],
					'error' => 'Only one throttling step is allowed.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'LLD two different discard unchanged with heartbeat',
						'Key' => 'lld-two-different-discard-uncahnged-with-heartbeat'
					],
					'preprocessing' => [
						['type' => 'Discard unchanged with heartbeat', 'parameter_1' => '1'],
						['type' => 'Discard unchanged with heartbeat', 'parameter_1' => '2']
					],
					'error' => 'Only one throttling step is allowed.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'LLD Discard unchanged with heartbeat empty',
						'Key' => 'lld-discard-uncahnged-with-heartbeat-empty'
					],
					'preprocessing' => [
						['type' => 'Discard unchanged with heartbeat']
					],
					'error' => 'Invalid parameter "params": cannot be empty.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'LLD Discard unchanged with heartbeat symbols',
						'Key' => 'lld-discard-uncahnged-with-heartbeat-symbols'
					],
					'preprocessing' => [
						['type' => 'Discard unchanged with heartbeat', 'parameter_1' => '3g!@#$%^&*()-=']
					],
					'error' => 'Invalid parameter "params": a time unit is expected.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'LLD Discardunchanged with heartbeat letters string',
						'Key' => 'lld-discard-uncahnged-with-heartbeat-letters-string'
					],
					'preprocessing' => [
						['type' => 'Discard unchanged with heartbeat', 'parameter_1' => 'abc']
					],
					'error' => 'Invalid parameter "params": a time unit is expected.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'LLD Discard unchanged with heartbeat comma',
						'Key' => 'lld-discard-uncahnged-with-heartbeat-comma',
					],
					'preprocessing' => [
						['type' => 'Discard unchanged with heartbeat', 'parameter_1' => '1,5']
					],
					'error' => 'Invalid parameter "params": a time unit is expected.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'LLD Discard unchanged with heartbeat dot',
						'Key' => 'lld-discard-uncahnged-with-heartbeat-dot',
					],
					'preprocessing' => [
						['type' => 'Discard unchanged with heartbeat', 'parameter_1' => '1.5']
					],
					'error' => 'Invalid parameter "params": a time unit is expected.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'LLD Discard unchanged with heartbeat negative',
						'Key' => 'lld-discard-uncahnged-with-heartbeat-negative',
					],
					'preprocessing' => [
						['type' => 'Discard unchanged with heartbeat', 'parameter_1' => '-3']
					],
					'error' => 'Invalid parameter "params": value must be one of 1-788400000.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'LLD Discard unchanged with heartbeat zero',
						'Key' => 'lld-discard-uncahnged-with-heartbeat-zero'
					],
					'preprocessing' => [
						['type' => 'Discard unchanged with heartbeat', 'parameter_1' => '0']
					],
					'error' => 'Invalid parameter "params": value must be one of 1-788400000.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'LLD Discard unchanged with heartbeat maximum',
						'Key' => 'lld-uncahnged-with-heartbeat-max'
					],
					'preprocessing' => [
						['type' => 'Discard unchanged with heartbeat', 'parameter_1' => '788400001']
					],
					'error' => 'Invalid parameter "params": value must be one of 1-788400000.'
				]
			],
			// Successful creation of LLD with preprocessing steps.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Add JavaScript multiline preprocessing',
						'Key' => 'item.javascript.multiline.preprocessing'
					],
					'preprocessing' => [
						['type' => 'JavaScript', 'parameter_1' => "  Test line 1\nTest line 2\nTest line 3   "]
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'LLD all preprocessing steps',
						'Key' => 'lld-all-preprocessing-steps'
					],
					'preprocessing' => [
						['type' => 'Regular expression', 'parameter_1' => 'expression', 'parameter_2' => '\1'],
						['type' => 'JSONPath', 'parameter_1' => '$.data.test'],
						['type' => 'JavaScript', 'parameter_1' => 'Test JavaScript'],
						['type' => 'Does not match regular expression', 'parameter_1' => 'Pattern'],
						['type' => 'Check for error in JSON', 'parameter_1' => '$.new.path'],
						['type' => 'Discard unchanged with heartbeat', 'parameter_1' => '30']
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'LLD double preprocessing steps',
						'Key' => 'lld-double-preprocessing-steps'
					],
					'preprocessing' => [
						['type' => 'Regular expression', 'parameter_1' => 'expression1', 'parameter_2' => '\1'],
						['type' => 'Regular expression', 'parameter_1' => 'expression2', 'parameter_2' => '\2'],
						['type' => 'JSONPath', 'parameter_1' => '$.data.test1'],
						['type' => 'JSONPath', 'parameter_1' => '$.data.test2'],
						['type' => 'Does not match regular expression', 'parameter_1' => 'Pattern1'],
						['type' => 'Does not match regular expression', 'parameter_1' => 'Pattern2'],
						['type' => 'JavaScript', 'parameter_1' => 'Test JavaScript'],
						['type' => 'JavaScript', 'parameter_1' => 'Test JavaScript'],
						['type' => 'Check for error in JSON', 'parameter_1' => '$.new.path1'],
						['type' => 'Check for error in JSON', 'parameter_1' => '$.new.path2']
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'LLD symbols preprocessing steps',
						'Key' => 'lld-symbols-preprocessing-steps'
					],
					'preprocessing' => [
						['type' => 'Regular expression', 'parameter_1' => '1a!@#$%^&*()-=', 'parameter_2' => '2b!@#$%^&*()-='],
						['type' => 'JSONPath', 'parameter_1' => '3c!@#$%^&*()-='],
						['type' => 'Does not match regular expression', 'parameter_1' => '4d!@#$%^&*()-='],
						['type' => 'JavaScript', 'parameter_1' => '5d!@#$%^&*()-='],
						['type' => 'Check for error in JSON', 'parameter_1' => '5e!@#$%^&*()-=']
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'LLD user macros preprocessing steps',
						'Key' => 'lld-macros-preprocessing-steps'
					],
					'preprocessing' => [
						['type' => 'Regular expression', 'parameter_1' => '{$PATTERN}', 'parameter_2' => '{$OUTPUT}'],
						['type' => 'JSONPath', 'parameter_1' => '{$PATH}'],
						['type' => 'Does not match regular expression', 'parameter_1' => '{$PATTERN2}'],
						['type' => 'JavaScript', 'parameter_1' => '{$JAVASCRIPT}'],
						['type' => 'Check for error in JSON', 'parameter_1' => '{$PATH2}'],
						['type' => 'Discard unchanged with heartbeat', 'parameter_1' => '{$HEARTBEAT}']
					]
				]
			]
		];
	}

	/**
	 * @dataProvider getCreateAllStepsData
	 */
	public function testFormLowLevelDiscoveryPreprocessing_CreateAllSteps($data) {
		$this->executeCreate($data, $this->link, $this->selector, $this->success_message, $this->ready_link, $this->fail_message);
	}

	public static function getCreatePrometheusData() {
		return [
			// Prometheus to JSON validation.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON first parameter starts with digits',
						'Key' => 'json-prometeus-digits-first-parameter',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '1name_of_metric']
					],
					'error' => 'Incorrect value for field "params": invalid Prometheus pattern.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON wrong equals operator',
						'Key' => 'json-prometeus-wrong-equals-operator',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '{__name__=~"<regex>"}=1']
					],
					'error' => 'Incorrect value for field "params": invalid Prometheus pattern.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON unsupported operator >',
						'Key' => 'json-prometeus-unsupported-operator-1',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '{__name__=~"<regex>"}>1'],
					],
					'error' => 'Incorrect value for field "params": invalid Prometheus pattern.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON unsupported operator <',
						'Key' => 'json-prometeus-unsupported-operator-2',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '{__name__=~"<regex>"}<1'],
					],
					'error' => 'Incorrect value for field "params": invalid Prometheus pattern.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON unsupported operator !==',
						'Key' => 'json-prometeus-unsupported-operator-3',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '{__name__=~"<regex>"}!==1'],
					],
					'error' => 'Incorrect value for field "params": invalid Prometheus pattern.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON unsupported operator >=',
						'Key' => 'json-prometeus-unsupported-operator-4',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '{__name__=~"<regex>"}>=1'],
					],
					'error' => 'Incorrect value for field "params": invalid Prometheus pattern.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON unsupported operator =<',
						'Key' => 'json-prometeus-unsupported-operator-5',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '{__name__=~"<regex>"}=<1'],
					],
					'error' => 'Incorrect value for field "params": invalid Prometheus pattern.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON unsupported label operator !=',
						'Key' => 'json-prometeus-unsupported-label-operator-1',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '{label_name!="regex_pattern"}'],
					],
					'error' => 'Incorrect value for field "params": invalid Prometheus pattern.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON unsupported label operator !~',
						'Key' => 'json-prometeus-unsupported-label-operator-2',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '{label_name!~"<regex>"}'],
					],
					'error' => 'Incorrect value for field "params": invalid Prometheus pattern.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON duplicate metric condition',
						'Key' => 'json-duplicate-metric-condition',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => 'cpu_system{__name__="metric_name"}'],
					],
					'error' => 'Incorrect value for field "params": invalid Prometheus pattern.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON wrong parameter - space',
						'Key' => 'json-wrong-parameter-space',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON',  'parameter_1' => 'cpu usage_system']
					],
					'error' => 'Incorrect value for field "params": invalid Prometheus pattern.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON wrong parameter - slash',
						'Key' => 'json-wrong-parameter-slash',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON',  'parameter_1' => 'cpu\\']

					],
					'error' => 'Incorrect value for field "params": invalid Prometheus pattern.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Prometheus to JSON wrong parameter - digits',
						'Key' => 'json-wrong-parameter-digits',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON',  'parameter_1' => '123']

					],
					'error' => 'Incorrect value for field "params": invalid Prometheus pattern.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Prometheus to JSON wrong first parameter - pipe',
						'Key' => 'json-wrong-parameter-pipe',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => 'metric==1e|5']

					],
					'error' => 'Incorrect value for field "params": invalid Prometheus pattern.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Prometheus to JSON wrong first parameter - slash',
						'Key' => 'json-wrong-parameter-slash',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '{label="value\"}']

					],
					'error' => 'Incorrect value for field "params": invalid Prometheus pattern.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item Prometheus to JSON wrong first parameter - LLD macro',
						'Key' => 'json-wrong-first-parameter-macro',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '{#METRICNAME}==1']

					],
					'error' => 'Incorrect value for field "params": invalid Prometheus pattern.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Item duplicate Prometeus to JSON steps',
						'Key' => 'duplicate-prometheus-to-json-steps',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => 'cpu_usage_system_1'],
						['type' => 'Prometheus to JSON', 'parameter_1' => 'cpu_usage_system_1']
					],
					'error' => 'Only one Prometheus step is allowed.'
				]
			],
			// Successful Prometheus to JSON creation.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON empty first parameter',
						'Key' => 'json-prometeus-empty-first-parameter',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '']
					],
					'error' => 'Incorrect value for field "params": first parameter is expected.'
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON first parameter +inf',
						'Key' => 'json-prometeus-plus-inf',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => 'cpu_usage_system==+inf']
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON first parameter inf',
						'Key' => 'json-prometeus-inf',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '{__name__="metric_name"}==inf']
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON first parameter -inf',
						'Key' => 'json-prometeus-negative-inf',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '{__name__=~"<regex>"}==-inf']
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON first parameter nan',
						'Key' => 'json-prometeus-nan',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '{__name__="metric_name"}==nan']
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON first parameter exp',
						'Key' => 'json-prometeus-exp',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => 'cpu_usage_system==3.5180e+11']
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON first parameter ==1',
						'Key' => 'json-prometeus-neutral-digit',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '{__name__="metric_name"}==1']
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON first parameters ==+1',
						'Key' => 'json-prometeus-positive-digit',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '{__name__="metric_name"}==+1']
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON first parameters ==-1',
						'Key' => 'json-prometeus-negative-digit',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '{__name__="metric_name"}==-1']
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON label operator =',
						'Key' => 'json-prometeus-label-operator-equal-strong',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '{label_name="name"}']
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON label operator =~',
						'Key' => 'json-prometeus-label-operator-contains',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '{label_name=~"name"}']
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Trailing spaces',
						'Key' => 'json-prometeus-space-in-parameter',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '  metric  ']
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON slashes in pattern',
						'Key' => 'json-prometeus-slashes-pattern',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '{label="value\\\\"}']
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON user macros in parameter',
						'Key' => 'json-prometeus-macros-1',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '{$METRIC_NAME}==1']
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON user macros in parameter',
						'Key' => 'json-prometeus-macros-2',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '{__name__="{$METRIC_NAME}"}']
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON user macros in parameters',
						'Key' => 'json-prometeus-macros-3',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '{{$LABEL_NAME}="<label value>"}']
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Item Prometeus to JSON user macros in parameters',
						'Key' => 'json-prometeus-macros-4',
					],
					'preprocessing' => [
						['type' => 'Prometheus to JSON', 'parameter_1' => '{label_name="{$LABEL_VALUE}"}']
					]
				]
			]
		];
	}

	/**
	 * @dataProvider getCreatePrometheusData
	 */
	public function testFormLowLevelDiscoveryPreprocessing_CreatePrometheus($data) {
		$this->executeCreate($data, $this->link, $this->selector, $this->success_message, $this->ready_link, $this->fail_message);
	}

	/**
	 * @dataProvider getCustomOnFailData
	 */
	public function testFormLowLevelDiscoveryPreprocessing_CustomOnFail($data) {
		$this->executeCustomOnFail($data, $this->link, $this->selector, $this->success_message, $this->ready_link);
	}

	/**
	 * @dataProvider getCustomOnFailValidationData
	 */
	public function testFormLowLevelDiscoveryPreprocessing_CustomOnFailValidation($data) {
		$this->executeCustomOnFailValidation($data, $this->link, $this->selector, $this->success_message, $this->fail_message);
	}

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
					]
				]
			]
		];
	}

	/**
	 * @dataProvider getPreprocessingData
	 */
	public function testFormLowLevelDiscoveryPreprocessing_PreprocessingInheritanceFromTemplate($preprocessing) {
		$fields = [
			'Name' => 'Templated LLD with Preprocessing steps',
			'Key' => 'templated-lld-with-preprocessing-steps'
		];

		$link = 'host_discovery.php?hostid='.self::INHERITANCE_TEMPLATE_ID;
		$ready_link = 'host_discovery.php?hostid='.self::INHERITANCE_HOST_ID;
		$selector = 'Create discovery rule';
		$success_message = 'Discovery rule created';

		$this->executeTestInheritance($preprocessing, $link, $selector, $fields, $success_message, $ready_link);
	}
}
