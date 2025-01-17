<?php declare(strict_types = 0);
/*
** Zabbix
** Copyright (C) 2001-2024 Zabbix SIA
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


class CControllerCopyCreate extends CController {

	protected function init(): void {
		$this->setPostContentType(self::POST_CONTENT_TYPE_JSON);
	}

	protected function checkInput(): bool {
		$fields = [
			'copy_targetids' =>	'required|array|not_empty',
			'itemids' =>		'array_db items.itemid',
			'triggerids' =>		'array_db triggers.triggerid',
			'graphids' =>		'array_db graphs.graphid',
			'copy_type' =>		'required|in '.implode(',', [
									COPY_TYPE_TO_HOST_GROUP, COPY_TYPE_TO_HOST, COPY_TYPE_TO_TEMPLATE,
									COPY_TYPE_TO_TEMPLATE_GROUP
								]),
			'source' => 'required|in '.implode(',', ['items', 'triggers', 'graphs'])
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			$this->setResponse(
				(new CControllerResponseData(['main_block' => json_encode([
					'error' => [
						'messages' => array_column(get_and_clear_messages(), 'message')
					]
				])]))->disableView()
			);
		}

		return $ret;
	}

	protected function checkPermissions(): bool {
		$source = $this->getInput('source');
		$copy_type = $this->getInput('copy_type');

		if (($copy_type == COPY_TYPE_TO_HOST || $copy_type == COPY_TYPE_TO_HOST_GROUP)
				&& !$this->checkAccess(CRoleHelper::UI_CONFIGURATION_HOSTS)) {
			return false;
		}
		elseif (($copy_type == COPY_TYPE_TO_TEMPLATE || $copy_type == COPY_TYPE_TO_TEMPLATE_GROUP)
				&& !$this->checkAccess(CRoleHelper::UI_CONFIGURATION_TEMPLATES)) {
			return false;
		}

		if (!$this->checkTargetPermissions($copy_type)) {
			return false;
		}

		if ($source === 'items' && $this->hasInput('itemids')) {
			$items_count = API::Item()->get([
				'countOutput' => true,
				'itemids' => $this->getInput('itemids')
			]);

			return $items_count == count($this->getInput('itemids'));
		}
		elseif ($source === 'triggers' && $this->hasInput('triggerids')) {
			$triggers_count = API::Trigger()->get([
				'countOutput' => true,
				'triggerids' => $this->getInput('triggerids')
			]);

			return $triggers_count == count($this->getInput('triggerids'));
		}
		elseif ($source === 'graphs' && $this->hasInput('graphids')) {
			$graphs_count = API::Graph()->get([
				'countOutput' => true,
				'graphids' => $this->getInput('graphids')
			]);

			return $graphs_count == count($this->getInput('graphids'));
		}

		return false;
	}

	private function checkTargetPermissions(int $copy_type): bool {
		$copy_targetids = $this->getInput('copy_targetids');

		switch ($copy_type) {
			case COPY_TYPE_TO_HOST:
				$copy_targets = API::Host()->get([
					'countOutput' => true,
					'hostids' => $copy_targetids,
					'editable' => true
				]);

				return $copy_targets == count($copy_targetids);

			case COPY_TYPE_TO_TEMPLATE:
				$copy_targets = API::Template()->get([
					'countOutput' => true,
					'templateids' => $copy_targetids,
					'editable' => true
				]);

				return $copy_targets == count($copy_targetids);

			case COPY_TYPE_TO_HOST_GROUP:
				$copy_targets = API::HostGroup()->get([
					'countOutput' => true,
					'groupids' => $copy_targetids
				]);

				return $copy_targets == count($copy_targetids);

			case COPY_TYPE_TO_TEMPLATE_GROUP:
				$copy_targets = API::TemplateGroup()->get([
					'countOutput' => true,
					'groupids' => $copy_targetids
				]);

				return $copy_targets == count($copy_targetids);
		}

		return false;
	}

	protected function doAction(): void {
		$dst_hosts = $this->getTargets();
		$output = [];

		switch ($this->getInput('source')) {
			case 'items':
				$itemids = $this->getInput('itemids');
				$success = !$dst_hosts || copyItemsToHosts('itemids', $itemids, $dst_hosts);

				$title = $success
					? _n('Item copied', 'Items copied', count($itemids))
					: _n('Cannot copy item', 'Cannot copy items', count($itemids));
				$messages = array_filter(['messages' => array_column(get_and_clear_messages(), 'message')]);
				$output = $success
					? ['success' => ['title' => $title] + $messages]
					: ['error' => ['title' => $title] + $messages];
				break;

			case 'triggers':
				$triggers_count = count($this->getInput('triggerids'));

				if ($this->copyTriggers(array_keys($dst_hosts))) {
					$output['success']['title'] = _n('Trigger copied', 'Triggers copied', $triggers_count);

					if ($messages = get_and_clear_messages()) {
						$output['success']['messages'] = array_column($messages, 'message');
					}
				}
				else {
					$output['error'] = [
						'title' => _n('Cannot copy trigger', 'Cannot copy triggers', $triggers_count),
						'messages' => array_column(get_and_clear_messages(), 'message')
					];
				}
				break;

			case 'graphs':
				$graphs_count = count($this->getInput('graphids'));

				if ($this->copyGraphs(array_keys($dst_hosts))) {
					$output['success']['title'] = _n('Graph copied', 'Graphs copied', $graphs_count);

					if ($messages = get_and_clear_messages()) {
						$output['success']['messages'] = array_column($messages, 'message');
					}
				}
				else {
					$output['error'] = [
						'title' => _n('Cannot copy graph', 'Cannot copy graphs', $graphs_count),
						'messages' => array_column(get_and_clear_messages(), 'message')
					];
				}
				break;
		}

		$this->setResponse(new CControllerResponseData(['main_block' => json_encode($output)]));
	}

	private function copyTriggers(array $copy_targetids): bool {
		$triggerids = $this->getInput('triggerids');

		return copyTriggersToHosts($copy_targetids, getRequest('hostid'), $triggerids);
	}

	private function copyGraphs(array $copy_targetids): bool {
		$graphids = $this->getInput('graphids');

		DBstart();
		foreach ($graphids as $graphid) {
			foreach ($copy_targetids as $host) {
				if (!copyGraphToHost($graphid, $host)) {
					return DBend(false);
				}
			}
		}

		return DBend(true);
	}

	private function getTargets(): array {
		$targetids = $this->getInput('copy_targetids', []);
		$copy_type = $this->getInput('copy_type');

		switch ($copy_type) {
			case COPY_TYPE_TO_HOST:
				$dst_hosts = API::Host()->get([
					'output' => ['host', 'status'],
					'selectInterfaces' => ['interfaceid', 'main', 'type', 'useip', 'ip', 'dns', 'port', 'details'],
					'hostids' => $targetids,
					'preservekeys' => true
				]);
				break;

			case COPY_TYPE_TO_HOST_GROUP:
				$dst_hosts = API::Host()->get([
					'output' => ['host', 'status'],
					'selectInterfaces' => ['interfaceid', 'main', 'type', 'useip', 'ip', 'dns', 'port', 'details'],
					'groupids' => $targetids,
					'preservekeys' => true
				]);
				break;

			case COPY_TYPE_TO_TEMPLATE:
				$dst_hosts = API::Template()->get([
					'output' => ['host'],
					'templateids' => $targetids,
					'preservekeys' => true
				]);
				break;

			case COPY_TYPE_TO_TEMPLATE_GROUP:
				$dst_hosts = API::Template()->get([
					'output' => ['host'],
					'groupids' => $targetids,
					'preservekeys' => true
				]);
				break;
		};

		if (in_array($copy_type, [COPY_TYPE_TO_TEMPLATE, COPY_TYPE_TO_TEMPLATE_GROUP])) {
			foreach ($dst_hosts as &$dst_host) {
				$dst_host['status'] = HOST_STATUS_TEMPLATE;
			}
			unset($dst_host);
		}

		return $dst_hosts;
	}
}
