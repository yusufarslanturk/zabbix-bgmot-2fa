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

require_once dirname(__FILE__).'/../../include/CWebTest.php';

/**
 * Trait for Macros in form related tests.
 */
trait MacrosTrait {

	/**
	 * Get macros table element with mapping set.
	 *
	 * @return CMultifieldTable
	 */
	protected function getMacrosTable() {
		return $this->query('id:tbl_macros')->asMultifieldTable([
			'mapping' => [
				'Macro' => [
					'selector' => 'xpath:./textarea',
					'class' => 'CElement'
				],
				'Value' => [
					'selector' => 'xpath:./textarea',
					'class' => 'CElement'
				],
				'Description' => [
					'selector' => 'xpath:./textarea',
					'class' => 'CElement'
				]
			]
		])->one();
	}

	/**
	 * Fill macros fields  with specified data.
	 *
	 * @param array $macros    data array where keys are fields label text and values are values to be put in fields
	 *
	 * @throws Exception
	 */
	public function fillMacros($macros, $defaultAction = USER_ACTION_ADD) {
		foreach ($macros as &$macro) {
			$macro['action'] = CTestArrayHelper::get($macro, 'action', $defaultAction);
		}
		unset($macro);

		$this->getMacrosTable()->fill($macros);
	}

	/**
	 * Get input fields of macros.
	 *
	 * @return array
	 */
	public function getMacros() {
		return $this->getMacrosTable()->getValue();
	}

	/**
	 * Remove macros rows.
	 *
	 * @return array
	 */
	public function removeMacros() {
		return $this->getMacrosTable()->clear();
	}

	/**
	 * Check if values of macros inputs match data from data provider.
	 *
	 * @param array $data    macros element values
	 */
	public function assertMacros($data = []) {
		$rows = [];
		foreach ($data as $i => $values) {
			if (CTestArrayHelper::get($values, 'action') !== USER_ACTION_REMOVE) {
				$rows[$i] = [
					'Macro' => CTestArrayHelper::get($values, 'Macro', ''),
					'Value' => CTestArrayHelper::get($values, 'Value', ''),
					'Description' => CTestArrayHelper::get($values, 'Description', ''),
				];
			}
		}

		if (!$rows) {
			$rows[] = [
				'Macro' => '',
				'Value' => '',
				'Description' => ''
			];
		}

		$this->assertEquals($rows, $this->getMacros(), 'Macros on a page does not match macros in data provider.');
	}
}
