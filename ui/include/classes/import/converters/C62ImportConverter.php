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

	private static $validator_schema;

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
		self::$validator_schema = (new CImportValidatorFactory($import_format))
			->getObject(ZABBIX_EXPORT_VERSION)
			->getSchema();

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
				self::sanitizeItemFields($host['items'], [
					'flags' => ZBX_FLAG_DISCOVERY_NORMAL,
					'hosts' => [['status' => HOST_STATUS_MONITORED]]
				]);
			}

			if (array_key_exists('discovery_rules', $host)) {
				$host['discovery_rules'] = self::convertDiscoveryRules($host['discovery_rules'], [
					'flags' => ZBX_FLAG_DISCOVERY_RULE,
					'hosts' => [['status' => HOST_STATUS_MONITORED]]
				]);
			}
		}
		unset($host);

		return $hosts;
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
				self::sanitizeItemFields($template['items'], [
					'flags' => ZBX_FLAG_DISCOVERY_NORMAL,
					'hosts' => [['status' => HOST_STATUS_TEMPLATE]]
				]);
			}

			if (array_key_exists('discovery_rules', $template)) {
				$template['discovery_rules'] = self::convertDiscoveryRules($template['discovery_rules'], [
					'flags' => ZBX_FLAG_DISCOVERY_RULE,
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
		self::sanitizeItemFields($discovery_rules, $internal_fields);

		foreach ($discovery_rules as &$discovery_rule) {
			if (array_key_exists('item_prototypes', $discovery_rule)) {
				$discovery_rule['item_prototypes'] = self::convertItemPrototypes($discovery_rule['item_prototypes'],
					['flags' => ZBX_FLAG_DISCOVERY_PROTOTYPE] + $internal_fields
				);
			}
		}
		unset($discovery_rule);

		return $discovery_rules;
	}

	private static function convertItemPrototypes(array $item_prototypes, array $internal_fields): array {
		self::sanitizeItemFields($item_prototypes, $internal_fields);

		foreach ($item_prototypes as &$item_prototype) {
			if (array_key_exists('inventory_link', $item_prototype)) {
				unset($item_prototype['inventory_link']);
			}
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
	 * @param array   $items
	 *        int     $items[<num>]['type']
	 *        string  $items[<num>]['key']
	 * @param array   $internal_fields
	 *        int     $internal_fields['flags']
	 *        int     $internal_fields['hosts'][0]['status']
	 *
	 * @return array
	 */
	private static function sanitizeItemFields(array &$items, array $internal_fields): void {
		$node_path = $internal_fields['hosts'][0]['status'] == HOST_STATUS_TEMPLATE
			? ['zabbix_export', 'templates', 'template']
			: ['zabbix_export', 'hosts', 'host'];

		switch ($internal_fields['flags']) {
			case ZBX_FLAG_DISCOVERY_NORMAL:
				array_push($node_path, 'items');
				$non_api_nodes = ['triggers'];
				break;

			case ZBX_FLAG_DISCOVERY_PROTOTYPE:
				array_push($node_path, 'discovery_rules', 'discovery_rule', 'item_prototypes');
				$non_api_nodes = ['trigger_prototypes'];
				break;

			case ZBX_FLAG_DISCOVERY_RULE:
				array_push($node_path, 'discovery_rules');
				$non_api_nodes = ['item_prototypes', 'trigger_prototypes', 'graph_prototypes', 'host_prototypes'];
				break;
		}

		$partial_import = [];
		$nested = &$partial_import;

		foreach ($node_path as $node) {
			$nested = &$nested[$node];
		}

		$nested = $items;

		$item_defaults = ['jmx_endpoint' => ZBX_DEFAULT_JMX_ENDPOINT] + DB::getDefaults('items');
		$api_items = (new CConstantImportConverter(self::$validator_schema))->convert($partial_import);

		while ($node = array_shift($node_path)) {
			$api_items = $api_items[$node];
		}

		foreach ($items as $i => &$item) {
			if (!array_key_exists('key', $item) || !array_key_exists('type', $item)) {
				continue;
			}

			$api_item = $api_items[$i];
			$api_item = CArrayHelper::renameKeys($api_item, ['key' => 'key_', 'interface_ref' => 'interfaceid']);
			$api_item = getSanitizedItemFields($api_item + ['templateid' => '0'] + $internal_fields + $item_defaults);
			$api_item = CArrayHelper::renameKeys($api_item, ['interfaceid' => 'interface_ref']);
			$api_item = array_merge(array_intersect_key($item, array_flip(['uuid', 'name', 'key', 'type'])), $api_item);

			$item = self::intersectFieldsRecursive($item, $api_item)
				+ array_intersect_key($item, array_flip($non_api_nodes));
		}
		unset($item);
	}

	private static function intersectFieldsRecursive(array $item, array $api_item): array {
		$item = array_intersect_key($item, $api_item);

		foreach ($item as $key => $value) {
			if (is_array($value)) {
				$item[$key] = self::intersectFieldsRecursive($item[$key], $api_item[$key]);
			}
		}

		return $item;
	}
}
