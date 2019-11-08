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


class CControllerHostMacrosList extends CController {

	protected function checkInput() {
		$fields = [
			'hostid'				=> 'db hosts.hostid',
			'form_id'				=> 'required|string',
			'macros'				=> 'array',
			'show_inherited_macros' => 'required|in 0,1',
			'templateids'			=> 'array_db hosts.hostid',
			'add_templates'			=> 'array_db hosts.hostid',
			'readonly'				=> 'required|in 0,1',
			'form_refresh'			=> 'int32'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			$this->setResponse((new CControllerResponseData([
				'main_block' => CJs::encodeJson(['errors' => getMessages()->toString()])
			]))->disableView());
		}

		return $ret;
	}

	protected function checkPermissions() {
		return ($this->getUserType() >= USER_TYPE_ZABBIX_ADMIN);
	}

	protected function doAction() {
		$hostid = $this->getInput('hostid', 0);
		$readonly = (bool) $this->getInput('readonly', 0);
		$macros = $this->getInput('macros', []);
		$show_inherited_macros = (bool) $this->getInput('show_inherited_macros', 0);
		$form_id = $this->getInput('form_id');
		$host = [];

		if ($macros) {
			$macros = cleanInheritedMacros($macros);

			// Remove empty new macro lines.
			foreach ($macros as $idx => $macro) {
				if (!array_key_exists('hostmacroid', $macro) && $macro['macro'] === '' && $macro['value'] === ''
						&& $macro['description'] === '') {
					unset($macros[$idx]);
				}
			}
		}

		if (($hostid != 0 && !$this->getInput('macros', []) && $this->getInput('form_refresh', 0) == 0) || $readonly) {
			if ($form_id === 'templatesForm') {
				$templates = API::Template()->get([
					'output' => [],
					'selectMacros' => ['hostmacroid', 'macro', 'value', 'description'],
					'templateids' => $hostid
				]);
				$host = reset($templates);
			}
			else {
				$hosts = API::Host()->get([
					'output' => ($form_id === 'hostsForm') ? ['flags'] : [],
					'selectMacros' => ['hostmacroid', 'macro', 'value', 'description'],
					'hostids' => $hostid,
					'templated_hosts' => ($form_id === 'hostPrototypeForm') ? true : null
				]);
				$host = reset($hosts);
			}

			$macros = $host['macros'];
		}

		if ($show_inherited_macros) {
			$macros = mergeInheritedMacros($macros, getInheritedMacros(
				array_merge($this->getInput('templateids', []), $this->getInput('add_templates', []))
			));
		}

		if ($macros) {
			$macros = array_values(order_macros($macros, 'macro'));
		}

		if (!$macros && $form_id !== 'hostPrototypeForm'
				&& (!array_key_exists('flags', $host) || $host['flags'] != ZBX_FLAG_DISCOVERY_CREATED)) {
			$macro = ['macro' => '', 'value' => '', 'description' => ''];
			if ($show_inherited_macros) {
				$macro['type'] = ZBX_PROPERTY_OWN;
			}
			$macros[] = $macro;
		}

		$data = [
			'readonly' => $readonly,
			'macros' => $macros,
			'show_inherited_macros' => $show_inherited_macros,
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		];

		$this->setResponse(new CControllerResponseData($data));
	}
}
