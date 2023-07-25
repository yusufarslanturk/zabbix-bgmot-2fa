<?php
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
 * @var CView $this
 */

require_once dirname(__FILE__).'/js/configuration.trigger.prototype.list.js.php';

$html_page = (new CHtmlPage())
	->setTitle(_('Trigger prototypes'))
	->setDocUrl(CDocHelper::getUrl($data['context'] === 'host'
		? CDocHelper::DATA_COLLECTION_HOST_TRIGGER_PROTOTYPE_LIST
		: CDocHelper::DATA_COLLECTION_TEMPLATES_TRIGGER_PROTOTYPE_LIST
	))
	->setControls(
		(new CTag('nav', true,
			(new CList())
				->addItem(
					new CRedirectButton(_('Create trigger prototype'),
						(new CUrl('trigger_prototypes.php'))
							->setArgument('parent_discoveryid', $data['parent_discoveryid'])
							->setArgument('form', 'create')
							->setArgument('context', $data['context'])
					)
				)
		))->setAttribute('aria-label', _('Content controls'))
	)
	->setNavigation(getHostNavigation('triggers', $this->data['hostid'], $this->data['parent_discoveryid']));

$url = (new CUrl('trigger_prototypes.php'))
	->setArgument('parent_discoveryid', $data['parent_discoveryid'])
	->setArgument('context', $data['context'])
	->getUrl();

// create form
$triggersForm = (new CForm('post', $url))
	->setName('triggersForm')
	->addVar('parent_discoveryid', $data['parent_discoveryid'], 'form_parent_discoveryid')
	->addVar('context', $data['context'], 'form_context');

// create table
$triggersTable = (new CTableInfo())
	->setHeader([
		(new CColHeader(
			(new CCheckBox('all_triggers'))->onClick("checkAll('".$triggersForm->getName()."', 'all_triggers', 'g_triggerid');")
		))->addClass(ZBX_STYLE_CELL_WIDTH),
		make_sorting_header(_('Severity'), 'priority', $data['sort'], $data['sortorder'], $url),
		make_sorting_header(_('Name'), 'description', $data['sort'], $data['sortorder'], $url),
		_('Operational data'),
		_('Expression'),
		make_sorting_header(_('Create enabled'), 'status', $data['sort'], $data['sortorder'], $url),
		make_sorting_header(_('Discover'), 'discover', $data['sort'], $data['sortorder'], $url),
		_('Tags')
	]);

$data['triggers'] = CMacrosResolverHelper::resolveTriggerExpressions($data['triggers'], [
	'html' => true,
	'sources' => ['expression', 'recovery_expression'],
	'context' => $data['context']
]);

$csrf_token = CCsrfTokenHelper::get('trigger_prototypes.php');

foreach ($data['triggers'] as $trigger) {
	$triggerid = $trigger['triggerid'];
	$trigger['discoveryRuleid'] = $data['parent_discoveryid'];

	// description
	$description = [];
	$description[] = makeTriggerTemplatePrefix($trigger['triggerid'], $data['parent_templates'],
		ZBX_FLAG_DISCOVERY_PROTOTYPE, $data['allowed_ui_conf_templates']
	);

	$description[] = new CLink(
		$trigger['description'],
		(new CUrl('trigger_prototypes.php'))
			->setArgument('form', 'update')
			->setArgument('parent_discoveryid', $data['parent_discoveryid'])
			->setArgument('triggerid', $triggerid)
			->setArgument('context', $data['context'])
	);

	if ($trigger['dependencies']) {
		$description[] = [BR(), bold(_('Depends on').':')];
		$triggerDependencies = [];

		foreach ($trigger['dependencies'] as $dependency) {
			$depTrigger = $data['dependencyTriggers'][$dependency['triggerid']];

			$depTriggerDescription =
				implode(', ', zbx_objectValues($depTrigger['hosts'], 'name')).NAME_DELIMITER.$depTrigger['description'];

			if ($depTrigger['flags'] == ZBX_FLAG_DISCOVERY_PROTOTYPE) {
				$triggerDependencies[] = (new CLink(
					$depTriggerDescription,
					(new CUrl('trigger_prototypes.php'))
						->setArgument('form', 'update')
						->setArgument('parent_discoveryid', $data['parent_discoveryid'])
						->setArgument('triggerid', $depTrigger['triggerid'])
						->setArgument('context', $data['context'])
				))->addClass(triggerIndicatorStyle($depTrigger['status']));
			}
			elseif ($depTrigger['flags'] == ZBX_FLAG_DISCOVERY_NORMAL) {
				$triggerDependencies[] = (new CLink(
					$depTriggerDescription,
					(new CUrl('triggers.php'))
						->setArgument('form', 'update')
						->setArgument('triggerid', $depTrigger['triggerid'])
						->setArgument('context', $data['context'])
				))->addClass(triggerIndicatorStyle($depTrigger['status']));
			}

			$triggerDependencies[] = BR();
		}
		array_pop($triggerDependencies);

		$description = array_merge($description, [(new CDiv($triggerDependencies))->addClass('dependencies')]);
	}

	// status
	$status = (new CLink(
		($trigger['status'] == TRIGGER_STATUS_DISABLED) ? _('No') : _('Yes'),
		(new CUrl('trigger_prototypes.php'))
			->setArgument('action', ($trigger['status'] == TRIGGER_STATUS_DISABLED)
				? 'triggerprototype.massenable'
				: 'triggerprototype.massdisable'
			)
			->setArgument('g_triggerid[]', $triggerid)
			->setArgument('parent_discoveryid', $data['parent_discoveryid'])
			->setArgument('context', $data['context'])
			->getUrl()
	))
		->addCsrfToken($csrf_token)
		->addClass(ZBX_STYLE_LINK_ACTION)
		->addClass(triggerIndicatorStyle($trigger['status']));


	$nodiscover = ($trigger['discover'] == ZBX_PROTOTYPE_NO_DISCOVER);
	$discover = (new CLink($nodiscover ? _('No') : _('Yes'),
			(new CUrl('trigger_prototypes.php'))
				->setArgument('g_triggerid[]', $triggerid)
				->setArgument('parent_discoveryid', $data['parent_discoveryid'])
				->setArgument('action',  $nodiscover
					? 'triggerprototype.discover.enable'
					: 'triggerprototype.discover.disable'
				)
				->setArgument('context', $data['context'])
				->getUrl()
		))
			->addCsrfToken($csrf_token)
			->addClass(ZBX_STYLE_LINK_ACTION)
			->addClass($nodiscover ? ZBX_STYLE_RED : ZBX_STYLE_GREEN);

	if ($trigger['recovery_mode'] == ZBX_RECOVERY_MODE_RECOVERY_EXPRESSION) {
		$expression = [
			_('Problem'), ': ', $trigger['expression'], BR(),
			_('Recovery'), ': ', $trigger['recovery_expression']
		];
	}
	else {
		$expression = $trigger['expression'];
	}

	// checkbox
	$checkBox = new CCheckBox('g_triggerid['.$triggerid.']', $triggerid);

	$triggersTable->addRow([
		$checkBox,
		CSeverityHelper::makeSeverityCell((int) $trigger['priority']),
		$description,
		$trigger['opdata'],
		(new CDiv($expression))->addClass(ZBX_STYLE_WORDWRAP),
		$status,
		$discover,
		$data['tags'][$triggerid]
	]);
}

// append table to form
$triggersForm->addItem([
	$triggersTable,
	$data['paging'],
	new CActionButtonList('action', 'g_triggerid',
		[
			'triggerprototype.massenable' => [
				'name' => _('Create enabled'),
				'confirm_singular' => _('Create triggers from selected prototype as enabled?'),
				'confirm_plural' => _('Create triggers from selected prototypes as enabled?'),
				'csrf_token' => $csrf_token
			],
			'triggerprototype.massdisable' => [
				'name' => _('Create disabled'),
				'confirm_singular' => _('Create triggers from selected prototype as disabled?'),
				'confirm_plural' => _('Create triggers from selected prototypes as disabled?'),
				'csrf_token' => $csrf_token
			],
			'popup.massupdate.triggerprototype' => [
				'content' => (new CSimpleButton(_('Mass update')))
					->addClass(ZBX_STYLE_BTN_ALT)
					->onClick(
						"openMassupdatePopup('popup.massupdate.triggerprototype', {".
							CCsrfTokenHelper::CSRF_TOKEN_NAME.": '".CCsrfTokenHelper::get('triggerprototype').
						"'}, {
							dialogue_class: 'modal-popup-static',
							trigger_element: this
						});"
					)
			],
			'triggerprototype.massdelete' => [
				'name' => _('Delete'),
				'confirm_singular' => _('Delete selected trigger prototype?'),
				'confirm_plural' => _('Delete selected trigger prototypes?'),
				'csrf_token' => $csrf_token
			]
		],
		$this->data['parent_discoveryid']
	)
]);

(new CScriptTag('
	view.init('.json_encode([
		'context' => $data['context']
	]).');
'))
	->setOnDocumentReady()
	->show();

$html_page
	->addItem($triggersForm)
	->show();
