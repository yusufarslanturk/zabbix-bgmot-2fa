<?php declare(strict_types = 0);
/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
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


/**
 * Converter for converting import data from 6.2 to 6.4.
 */
class C62ImportConverter extends CConverter {

	private static $import_format;

	private const DASHBOARD_WIDGET_TYPE = [
		CXmlConstantName::DASHBOARD_WIDGET_TYPE_CLOCK => CXmlConstantValue::DASHBOARD_WIDGET_TYPE_CLOCK,
		CXmlConstantName::DASHBOARD_WIDGET_TYPE_GRAPH_CLASSIC => CXmlConstantValue::DASHBOARD_WIDGET_TYPE_GRAPH_CLASSIC,
		CXmlConstantName::DASHBOARD_WIDGET_TYPE_GRAPH_PROTOTYPE => CXmlConstantValue::DASHBOARD_WIDGET_TYPE_GRAPH_PROTOTYPE,
		CXmlConstantName::DASHBOARD_WIDGET_TYPE_ITEM => CXmlConstantValue::DASHBOARD_WIDGET_TYPE_ITEM,
		CXmlConstantName::DASHBOARD_WIDGET_TYPE_PLAIN_TEXT => CXmlConstantValue::DASHBOARD_WIDGET_TYPE_PLAIN_TEXT,
		CXmlConstantName::DASHBOARD_WIDGET_TYPE_URL => CXmlConstantValue::DASHBOARD_WIDGET_TYPE_URL
	];

	/**
	 * Convert import data from 6.2 to 6.4 version.
	 *
	 * @param array  $data
	 * @param string $format
	 *
	 * @return array
	 */
	public function convert(array $data, string $import_format = CXmlValidatorGeneral::XML): array {
		self::$import_format = $import_format;

		$data['zabbix_export']['version'] = '6.4';

		unset($data['zabbix_export']['date']);

		if (array_key_exists('hosts', $data['zabbix_export'])) {
			$data['zabbix_export']['hosts'] = self::convertHosts($data['zabbix_export']['hosts']);
		}

		if (array_key_exists('templates', $data['zabbix_export'])) {
			$data['zabbix_export']['templates'] = self::convertTemplates($data['zabbix_export']['templates']);
		}

		if (array_key_exists('media_types', $data['zabbix_export'])) {
			$data['zabbix_export']['media_types'] = self::convertMediaTypes($data['zabbix_export']['media_types']);
		}

		return $data;
	}

	/**
	 * Convert hosts.
	 *
	 * @param array $hosts
	 *
	 * @return array
	 */
	private static function convertHosts(array $hosts): array {
		foreach ($hosts as &$host) {
			if (array_key_exists('items', $host)) {
				$host['items'] = self::convertItems($host['items'], [
					'flags' => ZBX_FLAG_DISCOVERY_NORMAL,
					'templateid' => 0,
					'hosts' => [['status' => HOST_STATUS_MONITORED]]
				]);
			}

			if (array_key_exists('discovery_rules', $host)) {
				$host['discovery_rules'] = self::convertDiscoveryRules($host['discovery_rules'], [
					'flags' => ZBX_FLAG_DISCOVERY_RULE,
					'templateid' => 0,
					'hosts' => [['status' => HOST_STATUS_MONITORED]]
				]);
			}
		}
		unset($host);

		return $hosts;
	}

	private static function convertItems(array $items, array $internal_fields): array {
		foreach ($items as &$item) {
			$item = self::getSanitizedItemFields($item, $internal_fields);
		}
		unset($item);

		return $items;
	}

	/**
	 * Convert templates.
	 *
	 * @param array $templates
	 *
	 * @return array
	 */
	private static function convertTemplates(array $templates): array {
		foreach ($templates as &$template) {
			if (array_key_exists('items', $template)) {
				$template['items'] = self::convertItems($template['items'], [
					'flags' => ZBX_FLAG_DISCOVERY_NORMAL,
					'templateid' => 1,
					'hosts' => [['status' => HOST_STATUS_TEMPLATE]]
				]);
			}

			if (array_key_exists('discovery_rules', $template)) {
				$template['discovery_rules'] = self::convertDiscoveryRules($template['discovery_rules'], [
					'flags' => ZBX_FLAG_DISCOVERY_RULE,
					'templateid' => 1,
					'hosts' => [['status' => HOST_STATUS_TEMPLATE]]
				]);
			}

			if (array_key_exists('dashboards', $template)) {
				$template['dashboards'] = self::convertDashboards($template['dashboards']);
			}
		}
		unset($template);

		return $templates;
	}

	private static function convertDiscoveryRules(array $discovery_rules, array $internal_fields): array {
		foreach ($discovery_rules as &$discovery_rule) {
			if (array_key_exists('item_prototypes', $discovery_rule)) {
				$discovery_rule['item_prototypes'] = self::convertItemPrototypes($discovery_rule['item_prototypes'],
					['flags' => ZBX_FLAG_DISCOVERY_PROTOTYPE] + $internal_fields
				);
			}

			$discovery_rule = self::getSanitizedItemFields($discovery_rule, $internal_fields);
		}
		unset($discovery_rule);

		return $discovery_rules;
	}

	private static function convertItemPrototypes(array $item_prototypes, array $internal_fields): array {
		foreach ($item_prototypes as &$item_prototype) {
			if (array_key_exists('inventory_link', $item_prototype)) {
				unset($item_prototype['inventory_link']);
			}

			$item_prototype = self::getSanitizedItemFields($item_prototype, $internal_fields);
		}
		unset($item_prototype);

		return $item_prototypes;
	}

	/**
	 * Convert dashboards.
	 *
	 * @param array $dashboards
	 *
	 * @return array
	 */
	private static function convertDashboards(array $dashboards): array {
		foreach ($dashboards as &$dashboard) {
			if (!array_key_exists('pages', $dashboard)) {
				continue;
			}

			foreach ($dashboard['pages'] as &$dashboard_page) {
				if (!array_key_exists('widgets', $dashboard_page)) {
					continue;
				}

				foreach ($dashboard_page['widgets'] as &$widget) {
					$widget['type'] = self::DASHBOARD_WIDGET_TYPE[$widget['type']];
				}
				unset($widget);
			}
			unset($dashboard_page);
		}
		unset($dashboard);

		return $dashboards;
	}

	/**
	 * Convert media types.
	 *
	 * @static
	 *
	 * @param array $media_types
	 *
	 * @return array
	 */
	private static function convertMediaTypes(array $media_types): array {
		foreach ($media_types as &$media_type) {
			if ($media_type['type'] == CXmlConstantName::SCRIPT && array_key_exists('parameters', $media_type)) {
				$parameters = [];
				$sortorder = 0;

				foreach ($media_type['parameters'] as $value) {
					$parameters[] = ['sortorder' => (string) $sortorder++, 'value' => $value];
				}

				$media_type['parameters'] =  $parameters;
			}
		}
		unset($media_type);

		return $media_types;
	}

	/**
	 * Get sanitized item fields of given import input.
	 *
	 * @param array   $input
	 *        int     $input['type']
	 *        string  $input['key']
	 * @param array   $internal_fields
	 *        int     $internal_fields['flags']
	 *        int     $internal_fields['templateid']
	 *        int     $internal_fields['hosts'][0]['status']
	 *
	 * @return array
	 */
	private static function getSanitizedItemFields(array $input, array $internal_fields): array {
		static $schema;

		if ($schema === null) {
			$import_validator_factory = new CImportValidatorFactory(self::$import_format);
			$schema = $import_validator_factory
				->getObject(ZABBIX_EXPORT_VERSION)
				->getSchema();
		}

		if (!array_key_exists('key', $input) || !array_key_exists('type', $input)) {
			return $input;
		}

		$item_convert = [
			'zabbix_export' => [
				'item' => $input
			]
		];
		$api_input = (new CConstantImportConverter($schema))->convert($item_convert)['zabbix_export']['item'];
		$api_input = CArrayHelper::renameKeys($api_input, ['key' => 'key_']);
		$api_input = getSanitizedItemFields($api_input + $internal_fields + DB::getDefaults('items'));
		$api_input = array_merge(array_intersect_key($input, array_flip(['uuid', 'name', 'key', 'type'])), $api_input);
		$non_api_nodes = array_flip([
			'triggers', 'trigger_prototypes', 'item_prototypes', 'graph_prototypes', 'host_prototypes'
		]);

		return self::intersectFieldsRecursive($input, $api_input) + array_intersect_key($input, $non_api_nodes);
	}

	private static function intersectFieldsRecursive(array $input, array $api_input): array {
		$input = array_intersect_key($input, $api_input);

		foreach ($input as $key => $value) {
			if (is_array($value)) {
				$input[$key] = self::intersectFieldsRecursive($input[$key], $api_input[$key]);
			}
		}

		return $input;
	}
}
