<?php
/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
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


/**
 * Trait for name-value parameters in form related tests.
 */
trait FormParametersTrait {

	protected $table_selector = 'id:parameters_table';

	/**
	 * Set custom selector for table.
	 *
	 * @param string $selector    table selector
	 */
	public function setTableSelector($selector) {
		$this->table_selector = $selector;
	}

	/**
	 * Get table element with mapping set.
	 *
	 * @return CMultifieldTable
	 */
	protected function getTable() {
		return $this->query($this->table_selector)->asMultifieldTable([
			'mapping' => [
				'Name' => [
					'name' => 'name',
					'class' => 'CElement'
				],
				'Value' => [
					'name' => 'value',
					'class' => 'CElement'
				]
			]
		])->one();
	}

	/**
	 * Fill parameters table with specified data.
	 *
	 * @param array $parameters    data array where keys are fields label text and values are values to be put in fields
	 *
	 * @throws Exception
	 */
	public function fillParameters($parameters, $defaultAction = USER_ACTION_ADD) {
		foreach ($parameters as &$parameter) {
			$parameter['action'] = CTestArrayHelper::get($parameter, 'action', $defaultAction);
		}
		unset($parameter);

		$this->getTable()->fill($parameters);
	}

	/**
	 * Remove parameters rows.
	 *
	 * @return $this
	 */
	public function removeParameters() {
		return $this->getTable()->clear();
	}

	/**
	 * Check if values of inputs match data from data provider.
	 *
	 * @param array $data    element values
	 */
	public function assertValues($data) {
		$rows = [];
		foreach ($data as $values) {
			$rows[] = [
				'name' => CTestArrayHelper::get($values, 'name', ''),
				'value' => CTestArrayHelper::get($values, 'value', ''),
			];
		}

		$this->assertEquals($rows, $this->getFormParameters(), 'Tags on a page does not match tags in data provider.');
	}

	/**
	 * Get input fields of parameters.
	 *
	 * @return array
	 */
	public function getFormParameters() {
		return $this->getTable()->getValue();
	}
}
