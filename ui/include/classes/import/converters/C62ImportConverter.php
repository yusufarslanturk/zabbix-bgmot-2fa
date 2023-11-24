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

	private const DASHBOARD_WIDGET_TYPE = [
		CXmlConstantName::DASHBOARD_WIDGET_TYPE_CLOCK => CXmlConstantValue::DASHBOARD_WIDGET_TYPE_CLOCK,
		CXmlConstantName::DASHBOARD_WIDGET_TYPE_GRAPH_CLASSIC => CXmlConstantValue::DASHBOARD_WIDGET_TYPE_GRAPH_CLASSIC,
		CXmlConstantName::DASHBOARD_WIDGET_TYPE_GRAPH_PROTOTYPE => CXmlConstantValue::DASHBOARD_WIDGET_TYPE_GRAPH_PROTOTYPE,
		CXmlConstantName::DASHBOARD_WIDGET_TYPE_ITEM => CXmlConstantValue::DASHBOARD_WIDGET_TYPE_ITEM,
		CXmlConstantName::DASHBOARD_WIDGET_TYPE_PLAIN_TEXT => CXmlConstantValue::DASHBOARD_WIDGET_TYPE_PLAIN_TEXT,
		CXmlConstantName::DASHBOARD_WIDGET_TYPE_URL => CXmlConstantValue::DASHBOARD_WIDGET_TYPE_URL
	];

	private const ITEM_MAIN_FIELDS = [
		ZBX_FLAG_DISCOVERY_NORMAL => ['name', 'type', 'key', 'value_type', 'units', 'history', 'trends',
			'valuemapid', 'inventory_link', 'logtimefmt', 'description', 'status', 'tags', 'preprocessing',
			'triggers'
		],
		ZBX_FLAG_DISCOVERY_PROTOTYPE => ['name', 'type', 'key', 'value_type', 'units', 'history', 'trends',
			'valuemapid', 'logtimefmt', 'description', 'status', 'discover', 'tags', 'preprocessing',
			'trigger_prototypes'
		],
		ZBX_FLAG_DISCOVERY_RULE => ['name', 'key', 'type', 'filter', 'item_prototypes', 'trigger_prototypes',
			'graph_prototypes', 'host_prototypes'
		]
	];

	private const ITEM_TYPE_FIELDS = [
		CXmlConstantName::ZABBIX_PASSIVE => ['interface_ref', 'delay'],
		CXmlConstantName::TRAP => ['trapper_hosts'],
		CXmlConstantName::SIMPLE => ['interface_ref', 'username', 'password', 'delay'],
		CXmlConstantName::INTERNAL => ['delay'],
		CXmlConstantName::ZABBIX_ACTIVE => ['delay'],
		CXmlConstantName::EXTERNAL => ['interface_ref', 'delay'],
		CXmlConstantName::ODBC => ['username', 'password', 'params', 'delay'],
		CXmlConstantName::IPMI => ['interface_ref', 'ipmi_sensor', 'delay'],
		CXmlConstantName::SSH => ['interface_ref', 'authtype', 'username', 'publickey', 'privatekey', 'password',
			'params', 'delay'
		],
		CXmlConstantName::TELNET => ['interface_ref', 'username', 'password', 'params', 'delay'],
		CXmlConstantName::CALCULATED => ['params', 'delay'],
		CXmlConstantName::JMX => ['interface_ref', 'jmx_endpoint', 'username', 'password', 'delay'],
		CXmlConstantName::SNMP_TRAP => ['interface_ref'],
		CXmlConstantName::DEPENDENT => ['master_itemid'],
		CXmlConstantName::HTTP_AGENT => ['url', 'query_fields', 'request_method', 'post_type', 'posts', 'headers',
			'status_codes', 'follow_redirects', 'retrieve_mode', 'output_format', 'http_proxy', 'interface_ref',
			'authtype', 'username', 'password', 'verify_peer', 'verify_host', 'ssl_cert_file', 'ssl_key_file',
			'ssl_key_password', 'timeout', 'delay', 'allow_traps', 'trapper_hosts'
		],
		CXmlConstantName::SNMP_AGENT => ['interface_ref', 'snmp_oid', 'delay'],
		CXmlConstantName::SCRIPT => ['parameters', 'params', 'timeout', 'delay']
	];

	/**
	 * Convert import data from 6.2 to 6.4 version.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function convert(array $data): array {
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
					'host_status' => HOST_STATUS_MONITORED
				]);
			}

			if (array_key_exists('discovery_rules', $host)) {
				$host['discovery_rules'] = self::convertDiscoveryRules($host['discovery_rules'], [
					'flags' => ZBX_FLAG_DISCOVERY_RULE,
					'host_status' => HOST_STATUS_MONITORED
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
					'host_status' => HOST_STATUS_TEMPLATE
				]);
			}

			if (array_key_exists('discovery_rules', $template)) {
				$template['discovery_rules'] = self::convertDiscoveryRules($template['discovery_rules'], [
					'flags' => ZBX_FLAG_DISCOVERY_RULE,
					'host_status' => HOST_STATUS_TEMPLATE
				]);
			}

			if (array_key_exists('dashboards', $template)) {
				$template['dashboards'] = self::convertDashboards($template['dashboards']);
			}
		}
		unset($template);

		return $templates;
	}

	/**
	* Convert discovery rules.
	*
	* @param array $discovery_rules
	*
	* @return array
	*/
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

	/**
	* Convert item prototypes.
	*
	* @param array $item_prototypes
	*
	* @return array
	*/
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
	 *        string  $items[<num>]['type']
	 *        string  $items[<num>]['key']
	 *        string  $items[<num>]['value_type']             (optional)
	 * @param array   $internal_fields
	 *        int     $internal_fields['flags']
	 *        int     $internal_fields['host_status']
	 *
	 * @return array
	 */
	private static function sanitizeItemFields(array &$items, array $internal_fields): void {
		$internal_fields['value_type'] = $internal_fields['flags'] == ZBX_FLAG_DISCOVERY_RULE
			? CXmlConstantName::TEXT
			: CXmlConstantName::UNSIGNED;

		foreach ($items as &$item) {
			if (!array_key_exists('key', $item) || !array_key_exists('type', $item)) {
				continue;
			}

			$field_names = array_merge(
				self::ITEM_MAIN_FIELDS[$internal_fields['flags']], self::ITEM_TYPE_FIELDS[$item['type']]
			);
			$field_names = self::getConditionalItemFieldNames($field_names, $item + $internal_fields);

			if ($internal_fields['host_status'] == HOST_STATUS_TEMPLATE) {
				array_unshift($field_names, 'uuid');
			}

			$item = array_intersect_key($item, array_flip($field_names));
		}
		unset($item);
	}

	private static function getConditionalItemFieldNames(array $field_names, array $item): array {
		return array_filter($field_names, static function ($field_name) use ($item): bool {
			switch ($field_name) {
				case 'units':
				case 'trends':
					return in_array($item['value_type'], [CXmlConstantName::FLOAT, CXmlConstantName::UNSIGNED]);

				case 'valuemapid':
					return in_array($item['value_type'],
						[CXmlConstantName::FLOAT, CXmlConstantName::CHAR, CXmlConstantName::UNSIGNED]
					);

				case 'inventory_link':
					return in_array($item['value_type'], [
						CXmlConstantName::FLOAT, CXmlConstantName::CHAR, CXmlConstantName::UNSIGNED,
						CXmlConstantName::TEXT
					]);

				case 'logtimefmt':
					return $item['value_type'] == CXmlConstantName::LOG;

				case 'interface_ref':
					return in_array($item['host_status'], [HOST_STATUS_MONITORED, HOST_STATUS_NOT_MONITORED]);

				case 'username':
				case 'password':
					return $item['type'] != CXmlConstantName::HTTP_AGENT || in_array($item['authtype'], [
						CXmlConstantName::BASIC, CXmlConstantName::NTLM, CXmlConstantName::KERBEROS,
						CXmlConstantName::DIGEST
					]);

				case 'delay':
					return $item['type'] != CXmlConstantName::ZABBIX_ACTIVE
						|| strncmp($item['key'], 'mqtt.get', 8) != 0;

				case 'trapper_hosts':
					return $item['type'] != CXmlConstantName::HTTP_AGENT
						|| $item['allow_traps'] == CXmlConstantName::YES;

				case 'publickey':
				case 'privatekey':
					return $item['authtype'] == CXmlConstantName::PUBLIC_KEY;
			}

			return true;
		});
	}

}
