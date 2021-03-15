<?php
/*
** Zabbix
** Copyright (C) 2001-2021 Zabbix SIA
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


class CControllerHintBox extends CController {

	protected function checkInput() {
		$fields = [
			'type' => 'required|in eventlist',
			'data' => 'array'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			$this->setResponse(new CControllerResponseData([]));
		}

		return $ret;
	}

	protected function checkPermissions() {
		return true;
	}

	protected function doAction() {
		$data = $this->hasInput('data') ? $this->getInput('data') : [];

		$hint_data = null;
		if ($this->getInput('type') === 'eventlist') {
			$hint_data = self::getHintDataEventList($data);
		}

		$output = [];

		if ($hint_data !== null) {
			$output['data'] = $hint_data;
		}

		$this->setResponse(new CControllerResponseData($output));
	}

	/**
	 * Get data for a hint with trigger events.
	 *
	 * @param array  $data
	 * @param string $data['triggerid']
	 * @param string $data['eventid_till']
	 * @param string $data['show_tags']
	 *
	 * @return array|null
	 */
	private static function getHintDataEventList(array $data) {
		$triggers = API::Trigger()->get([
			'output' => ['triggerid', 'expression', 'comments', 'url'],
			'triggerids' => $data['triggerid'],
			'preservekeys' => true
		]);

		if (!$triggers) {
			error(_('No permissions to referred object or it does not exist!'));

			return null;
		}

		$triggers = CMacrosResolverHelper::resolveTriggerUrls($triggers);
		$trigger = reset($triggers);

		$options = [
			'output' => ['eventid', 'r_eventid', 'clock', 'ns', 'acknowledged'],
			'select_acknowledges' => ['action'],
			'source' => EVENT_SOURCE_TRIGGERS,
			'object' => EVENT_OBJECT_TRIGGER,
			'eventid_till' => $data['eventid_till'],
			'objectids' => $data['triggerid'],
			'value' => TRIGGER_VALUE_TRUE,
			'sortfield' => ['eventid'],
			'sortorder' => ZBX_SORT_DOWN,
			'limit' => ZBX_WIDGET_ROWS
		];

		if ($data['show_tags'] != PROBLEMS_SHOW_TAGS_NONE) {
			$options['selectTags'] = ['tag', 'value'];
		}

		$problems = API::Event()->get($options);

		CArrayHelper::sort($problems, [
			['field' => 'clock', 'order' => ZBX_SORT_DOWN],
			['field' => 'ns', 'order' => ZBX_SORT_DOWN]
		]);

		$r_eventids = [];

		foreach ($problems as $problem) {
			$r_eventids[$problem['r_eventid']] = true;
		}
		unset($r_eventids[0]);

		$r_events = $r_eventids
			? API::Event()->get([
				'output' => ['clock', 'correlationid', 'userid'],
				'source' => EVENT_SOURCE_TRIGGERS,
				'object' => EVENT_OBJECT_TRIGGER,
				'eventids' => array_keys($r_eventids),
				'preservekeys' => true
			])
			: [];

		foreach ($problems as &$problem) {
			if (array_key_exists($problem['r_eventid'], $r_events)) {
				$problem['r_clock'] = $r_events[$problem['r_eventid']]['clock'];
				$problem['correlationid'] = $r_events[$problem['r_eventid']]['correlationid'];
				$problem['userid'] = $r_events[$problem['r_eventid']]['userid'];
			}
			else {
				$problem['r_clock'] = 0;
				$problem['correlationid'] = 0;
				$problem['userid'] = 0;
			}

			if (bccomp($problem['eventid'], $data['eventid_till']) == 0) {
				$trigger['comments'] = CMacrosResolverHelper::resolveTriggerDescription($trigger + [
					'clock' => $problem['clock'],
					'ns' => $problem['ns']
				], ['events' => true]);
			}
		}
		unset($problem);

		return [
			'trigger' => array_intersect_key($trigger, array_flip(['triggerid', 'comments', 'url'])),
			'problems' => $problems
		] + $data;
	}
}
