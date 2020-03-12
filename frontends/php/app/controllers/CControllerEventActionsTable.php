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


class CControllerEventActionsTable extends CController {

	/**
	 * @var array
	 */
	protected $event;

	protected function checkInput() {
		$fields = [
			'eventid' => 'required|db events.eventid'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			$this->setResponse(new CControllerResponseFatal());
		}

		return $ret;
	}

	protected function checkPermissions() {
		if ($this->getUserType() < USER_TYPE_ZABBIX_USER) {
			return false;
		}

		$this->event = API::Event()->get([
			'output' => ['eventid', 'r_eventid', 'clock'],
			'select_acknowledges' => ['userid', 'action', 'message', 'clock', 'new_severity', 'old_severity'],
			'eventids' => (array) $this->getInput('eventid')
		]);

		$this->event = reset($this->event);

		return (bool) $this->event;
	}

	protected function doAction() {
		$actions = $this->getEventActions($userids, $mediatypeids, $count);

		$users = $userids
			? API::User()->get([
				'output' => ['alias', 'name', 'surname'],
				'userids' => $userids,
				'preservekeys' => true
			])
			: [];

		$mediatypes = $mediatypeids
			? API::MediaType()->get([
				'output' => ['description'],
				'mediatypeids' => $mediatypeids,
				'preservekeys' => true
			])
			: [];

		$this->setResponse(new CControllerResponseData([
			'actions_table' => makeEventActionsTable($actions, $users, $mediatypes, select_config()),
			'foot_note' => ($count > ZBX_WIDGET_ROWS)
				? _s('Displaying %1$s of %2$s found', ZBX_WIDGET_ROWS, $count)
				: null
		]));
	}

	/**
	 * Get list of event actions including actions of resolve event.
	 *
	 * @param array $userids       List of userids referenced in alerts.
	 * @param array $mediatypeids  List of mediatypeids referenced in alerts.
	 * @param int   $count         Number of meaningful alerts (differs from length of returned list).
	 *
	 * @return array               List of actions sorted by time.
	 */
	protected function getEventActions(&$userids, &$mediatypeids, &$count) {
		$alert_eventids = [$this->event['eventid']];
		$r_events = [];

		if ($this->event['r_eventid']) {
			$alert_eventids[] = $this->event['r_eventid'];
			$r_events = API::Event()->get([
				'output' => ['clock'],
				'eventids' => (array) $this->event['r_eventid'],
				'preservekeys' => true
			]);
		}

		$config = select_config();
		$alerts = API::Alert()->get([
			'output' => ['alerttype', 'clock', 'error', 'eventid', 'mediatypeid', 'retries', 'status', 'userid'],
			'eventids' => $alert_eventids,
			'limit' => $config['search_limit']
		]);

		$event_actions = getSingleEventActions($this->event, $r_events, $alerts);

		$mediatypeids = array_keys($event_actions['mediatypeids']);
		$userids = array_keys($event_actions['userids']);
		$count = $event_actions['count'];

		return $event_actions['actions'];
	}
}
