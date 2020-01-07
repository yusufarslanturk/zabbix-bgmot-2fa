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

require_once dirname(__FILE__).'/../include/CWebTest.php';

/**
 * @backup media_type
 */
class testFormAdministrationMediaTypeWebhook extends CWebTest {

	// SQL query to get media_type and media_type_param tables to compare hash values.
	private $sql = 'SELECT * FROM media_type mt INNER JOIN media_type_param mtp ON mt.mediatypeid=mtp.mediatypeid';

	public function createUpdateWebhookData() {
		return [
			// Add webhook media type with default values and only space in script field.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Webhook with default fields',
						'Type' => 'Webhook',
						'Script' => ' '
					]
				]
			],
			// Add webhook media type without parameters.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Webhook without parameters',
						'Type' => 'Webhook',
						'Script' => 'all parameters should be removed'
					],
					'parameters' => [
						['Name' => 'URL', 'Action' => 'Remove'],
						['Name' => 'To', 'Action' => 'Remove'],
						['Name' => 'Subject', 'Action' => 'Remove'],
						['Name' => 'Message', 'Action' => 'Remove']
					]
				]
			],
			// Add webhook media type with enabled menu entry fields and changed options tab fields.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Webhook with enabled menu entry fields',
						'Type' => 'Webhook',
						'Include event menu entry' => true,
						'Menu entry name' => '{EVENT.TAGS.Name}',
						'Menu entry URL' => '{EVENT.TAGS.Url}',
						'Script' => 'Webhook with specified "Menu entry name" and "Menu entry URL" fields'
					],
					'options' => [
						'Attempts' => '10',
						'Attempt interval' => '1m'
					],
					'concurrent_sessions' => [
						'Custom' => '5'
					]
				]
			],
			// Add webhook media type with all possible parameters defined.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'All fields specified',
						'Type' => 'Webhook',
						'Script' => '!@#$%^&*()_+-=[]{};:"|,./<>?',
						'Timeout' => '1m',
						'Process tags' => true,
						'Include event menu entry' => true,
						'Menu entry name' => '{EVENT.TAGS."Returned value"}',
						'Menu entry URL' => 'http://zabbix.com/browse/{EVENT.TAGS."Returned value"}',
						'Description' => 'Webhook with all possible fields',
						'Enabled' => false
					],
					'options' => [
						'Attempts' => '5',
						'Attempt interval' => '20s'
					],
					'concurrent_sessions' => 'Unlimited',
					'parameters' => [
						['Name' => '1st new parameter', 'Value' => '1st new parameter value'],
						['Name' => '2nd parameter', 'Value' => '{2ND.PARAMETER}']
					]
				]
			],
			// Attempt to add a webhook media type with a blank name.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Type' => 'Webhook',
						'Script' => 'blank name webhook'
					],
					'error_message' => 'Incorrect value for field "name": cannot be empty.'
				]
			],
			// Attempt to add a webhook with a blank parameter name.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Webhook with blank parameter name',
						'Type' => 'Webhook',
						'Script' => 'blank parameter name webhook'
					],
					'parameters' => [
						['Name' => '', 'Value' => '{BLANK.NAME}']
					],
					'error_message' => 'Invalid parameter "/2/parameters/5/name": cannot be empty.'
				]
			],
			// Attempt to add a webhook without specifying the script field.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Webhook with empty script field',
						'Type' => 'Webhook'
					],
					'error_message' => 'Invalid parameter "/2/script": cannot be empty.'
				]
			],
			// Attempt to add a webhook with timeout equal to zero.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Webhook with zero timeout',
						'Type' => 'Webhook',
						'Script' => 'Zero timeout',
						'Timeout' => '0'
					],
					'error_message' => 'Invalid parameter "/2/timeout": value must be one of 1-60.'
				]
			],
			// Attempt to add a webhook with too large timeout.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Webhook with too large timeout',
						'Type' => 'Webhook',
						'Script' => 'Large timeout',
						'Timeout' => '61s'
					],
					'error_message' => 'Invalid parameter "/2/timeout": value must be one of 1-60.'
				]
			],
			// Attempt to add a webhook with too large timeout #2.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Webhook with too large timeout',
						'Type' => 'Webhook',
						'Script' => 'Large timeout',
						'Timeout' => '2m'
					],
					'error_message' => 'Invalid parameter "/2/timeout": value must be one of 1-60.'
				]
			],
			// Attempt to add a webhook with a string in the timeout field.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Webhook with too large timeout',
						'Type' => 'Webhook',
						'Script' => 'String in timeout',
						'Timeout' => '30seconds'
					],
					'error_message' => 'Invalid parameter "/2/timeout": a time unit is expected.'
				]
			],
			// Attempt to add a webhook with empty menu entry name.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Webhook with empty menu entry name',
						'Type' => 'Webhook',
						'Script' => 'Empty menu entry name',
						'Include event menu entry' => true,
						'Menu entry URL' => 'https://zabbix.com/{EVENT.TAGS."Returned value"}'
					],
					'error_message' => 'Incorrect value for field "event_menu_name": cannot be empty.'
				]
			],
			// Attempt to add a webhook with empty menu entry URL.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Webhook with empty menu entry URL',
						'Type' => 'Webhook',
						'Script' => 'Empty menu entry URL',
						'Include event menu entry' => true,
						'Menu entry name' => '{EVENT.TAGS."Returned tag"}'
					],
					'error_message' => 'Incorrect value for field "event_menu_url": cannot be empty.'
				]
			],
			// Attempt to add a webhook with incorrect menu entry URL format.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Webhook with incorrect menu entry URL',
						'Type' => 'Webhook',
						'Script' => 'Incorrect menu entry URL',
						'Include event menu entry' => true,
						'Menu entry name' => '{EVENT.TAGS."Returned tag"}',
						'Menu entry URL' => 'zabbix.com'
					],
					'error_message' => 'Invalid parameter "/2/event_menu_url": unacceptable URL.'
				]
			],
			// Attempt to add a webhook with an invalid macro in menu entry URL field.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Webhook with invalid macro in menu entry URL',
						'Type' => 'Webhook',
						'Script' => 'Invalid macro in menu entry URL',
						'Include event menu entry' => true,
						'Menu entry name' => '{EVENT.TAGS."Returned tag"}',
						'Menu entry URL' => '{INVALID.MACRO}'
					],
					'error_message' => 'Invalid parameter "/2/event_menu_url": unacceptable URL.'
				]
			],
			// Attempt to add a webhook with empty Attempts field.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Webhook with empty attempts field',
						'Type' => 'Webhook',
						'Script' => 'Empty attempts field'
					],
					'options' => [
						'Attempts' => ''
					],
					'error_message' => 'Incorrect value for field "maxattempts": must be between "1" and "10".'
				]
			],
			// Attempt to add a webhook with 0 in Attempts field.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Webhook with 0 in attempts field',
						'Type' => 'Webhook',
						'Script' => 'Zero Attempts'
					],
					'options' => [
						'Attempts' => '0'
					],
					'error_message' => 'Incorrect value for field "maxattempts": must be between "1" and "10".'
				]
			],
			// Attempt to add a webhook with too much Attempts.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Webhook with too much attempts',
						'Type' => 'Webhook',
						'Script' => 'Too much Attempts'
					],
					'options' => [
						'Attempts' => '11'
					],
					'error_message' => 'Incorrect value for field "maxattempts": must be between "1" and "10".'
				]
			],
			// Attempt to add a webhook with empty Attempt interval field.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Webhook with an empty Attempt interval',
						'Type' => 'Webhook',
						'Script' => 'Empty attempt interval'
					],
					'options' => [
						'Attempts' => '5',
						'Attempt interval' => ''
					],
					'error_message' => 'Incorrect value for field "attempt_interval": cannot be empty.'
				]
			],
			// Attempt to add a webhook with Attempt interval out of range.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Webhook with Attempt interval out of range',
						'Type' => 'Webhook',
						'Script' => 'Out of range attempt interval'
					],
					'options' => [
						'Attempts' => '5',
						'Attempt interval' => '61'
					],
					'error_message' => 'Incorrect value for field "attempt_interval": must be between "0" and "60".'
				]
			],
			// Attempt to add a webhook with custom concurrent sessions number out of range.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Webhook with Attempt interval out of range',
						'Type' => 'Webhook',
						'Script' => 'Out of range attempt interval'
					],
					'options' => [
						'Attempts' => '5',
						'Attempt interval' => '5'
					],
					'concurrent_sessions' => [
						'Custom' => '101'
					],
					'error_message' => 'Incorrect value for field "maxsessions": must be between "0" and "100".'
				]
			],
			// Change the type of media type.
			[
				[
					'update' => true,
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Webhook changed to SMS',
						'Type' => 'SMS',
						'GSM modem' => '/dev/ttyS0'
					]
				]
			],
			// Remove all webhook parameters and change timeout.
			[
				[
					'update' => true,
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Remove webhook parameters',
						'Type' => 'Webhook',
						'Timeout' => '10',
						'Script' => 'all parameters should be removed'
					],
					'parameters' => [
						['Name' => 'URL', 'Action' => 'Remove'],
						['Name' => 'To', 'Action' => 'Remove'],
						['Name' => 'Subject', 'Action' => 'Remove'],
						['Name' => 'Message', 'Action' => 'Remove']
					]
				]
			],
			// Update webhook script and add Menu entry parameters.
			[
				[
					'update' => true,
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'Add menu entry fields',
						'Type' => 'Webhook',
						'Include event menu entry' => true,
						'Menu entry name' => 'Menu entry name',
						'Menu entry URL' => 'https://zabbix.com',
						'Script' => 'New webhook script'
					]
				]
			],
			// Update all possible webhook parameters.
			[
				[
					'update' => true,
					'expected' => TEST_GOOD,
					'fields' => [
						'Name' => 'All fields updated',
						'Type' => 'Webhook',
						'Script' => '!@#$%^&*()_+-=[]{};:"|,./<>?',
						'Timeout' => '1m',
						'Process tags' => true,
						'Include event menu entry' => true,
						'Menu entry name' => '{EVENT.TAGS."Returned value"}',
						'Menu entry URL' => 'http://zabbix.com/browse/{EVENT.TAGS."Returned value"}',
						'Description' => 'This is the new description of this media type !@#$%^&*()_+-=[]{};:"|,./<>?',
						'Enabled' => false
					],
					'options' => [
						'Attempts' => '1',
						'Attempt interval' => '0'
					],
					'concurrent_sessions' => [
						'Custom' => '2'
					],
					'parameters' => [
						['Name' => 'URL', 'Action' => 'Remove'],
						['Name' => 'To', 'Action' => 'Remove'],
						['Name' => '1st new parameter', 'Value' => '1st new parameter value'],
						['Name' => '2nd parameter', 'Value' => '{2ND.PARAMETER}']
					]
				]
			],
			// Removing the name of a webhook media type.
			[
				[
					'update' => true,
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => '',
						'Type' => 'Webhook',
						'Script' => 'blank name webhook'
					],
					'error_message' => 'Incorrect value for field "name": cannot be empty.'
				]
			],
			// Changing the name of a webhook media type to a name that is already taken.
			[
				[
					'update' => true,
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Email',
						'Type' => 'Webhook',
						'Script' => 'blank name webhook'
					],
					'error_message' => 'Media type "Email" already exists.'
				]
			],
			// Adding a parameter with a blank name to the media type.
			[
				[
					'update' => true,
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Blank parameter name',
						'Type' => 'Webhook',
						'Script' => 'blank parameter name webhook'
					],
					'parameters' => [
						['Name' => '', 'Value' => '{BLANK.NAME}']
					],
					'error_message' => 'Invalid parameter "/1/parameters/5/name": cannot be empty.'
				]
			],
			// Removing the value of field script.
			[
				[
					'update' => true,
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Empty script field',
						'Type' => 'Webhook',
						'Script' => ''
					],
					'error_message' => 'Invalid parameter "/1/script": cannot be empty.'
				]
			],
			// Changing the value of timeout field to zero.
			[
				[
					'update' => true,
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Zero timeout',
						'Type' => 'Webhook',
						'Script' => 'Zero timeout',
						'Timeout' => '0'
					],
					'error_message' => 'Invalid parameter "/1/timeout": value must be one of 1-60.'
				]
			],
			// Increasing timeout too high.
			[
				[
					'update' => true,
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Too large timeout',
						'Type' => 'Webhook',
						'Script' => 'Large timeout',
						'Timeout' => '3d'
					],
					'error_message' => 'Invalid parameter "/1/timeout": value must be one of 1-60.'
				]
			],
			// Changing value of field timeout to a string
			[
				[
					'update' => true,
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'String in timeout field',
						'Type' => 'Webhook',
						'Script' => 'String in timeout',
						'Timeout' => '1minute'
					],
					'error_message' => 'Invalid parameter "/1/timeout": a time unit is expected.'
				]
			],
			// Add a menu entry URL without a menu entry name.
			[
				[
					'update' => true,
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Empty menu entry name',
						'Type' => 'Webhook',
						'Script' => 'Empty menu entry name',
						'Include event menu entry' => true,
						'Menu entry URL' => '{EVENT.TAGS.Address}'
					],
					'error_message' => 'Incorrect value for field "event_menu_name": cannot be empty.'
				]
			],
			// Add a menu entry name without a menu entry URL.
			[
				[
					'update' => true,
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Empty menu entry URL',
						'Type' => 'Webhook',
						'Script' => 'Empty menu entry URL',
						'Include event menu entry' => true,
						'Menu entry name' => '{EVENT.TAGS."Returned tag"}'
					],
					'error_message' => 'Incorrect value for field "event_menu_url": cannot be empty.'
				]
			],
			// Add a menu entry URL with an incorrect macro.
			[
				[
					'update' => true,
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Incorrect menu entry URL',
						'Type' => 'Webhook',
						'Script' => 'Incorrect menu entry URL',
						'Include event menu entry' => true,
						'Menu entry name' => '{EVENT.TAGS."Returned tag"}',
						'Menu entry URL' => '{URL}'
					],
					'error_message' => 'Invalid parameter "/1/event_menu_url": unacceptable URL.'
				]
			],
			// Remove the value of the attempts field.
			[
				[
					'update' => true,
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Empty attempts field',
						'Type' => 'Webhook',
						'Script' => 'Empty attempts field'
					],
					'options' => [
						'Attempts' => ''
					],
					'error_message' => 'Incorrect value for field "maxattempts": must be between "1" and "10".'
				]
			],
			// Set the value of Attempts field to 0.
			[
				[
					'update' => true,
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => '0 in attempts field',
						'Type' => 'Webhook',
						'Script' => 'Zero Attempts'
					],
					'options' => [
						'Attempts' => '0'
					],
					'error_message' => 'Incorrect value for field "maxattempts": must be between "1" and "10".'
				]
			],
			// Set the value of Attempts field too high.
			[
				[
					'update' => true,
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Too much attempts',
						'Type' => 'Webhook',
						'Script' => 'Too much Attempts'
					],
					'options' => [
						'Attempts' => '100'
					],
					'error_message' => 'Incorrect value for field "maxattempts": must be between "1" and "10".'
				]
			],
			// Set the value of Attempts field to some string.
			[
				[
					'update' => true,
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'String in attempts',
						'Type' => 'Webhook',
						'Script' => 'attempts in string format'
					],
					'options' => [
						'Attempts' => 'five'
					],
					'error_message' => 'Incorrect value for field "maxattempts": must be between "1" and "10".'
				]
			],
			// Remove the value of the attempt interval field.
			[
				[
					'update' => true,
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Empty Attempt interval',
						'Type' => 'Webhook',
						'Script' => 'Empty attempt interval'
					],
					'options' => [
						'Attempts' => '1',
						'Attempt interval' => ''
					],
					'error_message' => 'Incorrect value for field "attempt_interval": cannot be empty.'
				]
			],
			// Set a value of the attempt interval that is out of range.
			[
				[
					'update' => true,
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Attempt interval out of range',
						'Type' => 'Webhook',
						'Script' => 'Out of range attempt interval'
					],
					'options' => [
						'Attempts' => '5',
						'Attempt interval' => '61'
					],
					'error_message' => 'Incorrect value for field "attempt_interval": must be between "0" and "60".'
				]
			],
			// Set a string value in the attempt interval field.
			[
				[
					'update' => true,
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Attempt interval out of range',
						'Type' => 'Webhook',
						'Script' => 'String in attempt interval'
					],
					'options' => [
						'Attempts' => '5',
						'Attempt interval' => '10seconds'
					],
					'error_message' => 'Incorrect value for field "attempt_interval": must be between "0" and "60".'
				]
			],
			// Attempt to set concurrent sessions field to custom and set the value out of range.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Name' => 'Custom concurrent sessions out of range',
						'Type' => 'Webhook',
						'Script' => 'Custom concurrent sessions out of range'
					],
					'options' => [
						'Attempts' => '5'
					],
					'concurrent_sessions' => [
						'Custom' => '101'
					],
					'error_message' => 'Incorrect value for field "maxsessions": must be between "0" and "100".'
				]
			],
		];
	}

	/**
	* @backup media_type
	* @backup media_type_param
	* @dataProvider createUpdateWebhookData
	*/
	public function testFormAdministrationMediaTypeWebhook_CreateUpdate($data) {
		$old_hash = CDBHelper::getHash($this->sql);

		$this->page->login()->open('zabbix.php?action=mediatype.list');
		$button = CTestArrayHelper::get($data, 'update', false) ? 'link:Reference webhook' : 'button:Create media type';
		$this->query($button)->one()->WaitUntilClickable()->click();
		$form = $this->query('id:media_type_form')->asForm()->waitUntilVisible()->one();
		$form->fill($data['fields']);
		if (array_key_exists('parameters', $data)) {
			$this->fillParametersField($data);
		}
		// Fill fields in Operations tab if needed.
		if (CTestArrayHelper::get($data, 'options', false) || CTestArrayHelper::get($data, 'concurrent_sessions', false)) {
			$form->selectTab('Options');
			$this->fillOperationsTab($data, $form);
		}

		$form->submit();
		$this->page->waitUntilReady();

		// Check media type creation or update message.
		$good_title = CTestArrayHelper::get($data, 'update', false) ? 'Media type updated' : 'Media type added';
		$bad_title = CTestArrayHelper::get($data, 'update', false) ? 'Cannot update media type' : 'Cannot add media type';
		$this->assertMediaTypeMessage($data, $good_title, $bad_title);

		// Check that no DB changes took place in case of negative test.
		if ($data['expected'] === TEST_BAD) {
			$this->assertEquals($old_hash, CDBHelper::getHash($this->sql));
		}
		else {
			$this->checkMediaTypeFields($data);
		}
	}

	public function testFormAdministrationMediaTypeWebhook_SimpleUpdate() {
		$old_hash = CDBHelper::getHash($this->sql);

		$this->page->login()->open('zabbix.php?action=mediatype.list');
		$this->query('link:Reference webhook')->one()->WaitUntilClickable()->click();
		$form = $this->query('id:media_type_form')->asForm()->waitUntilVisible()->one();
		$form->submit();
		$this->page->waitUntilReady();

		$message = CMessageElement::find()->one();
		$this->assertTrue($message->isGood());
		$this->assertEquals('Media type updated', $message->getTitle());

		$this->assertEquals($old_hash, CDBHelper::getHash($this->sql));
	}

	public function testFormAdministrationMediaTypeWebhook_Clone() {
		// SQL for collecting all webhook mediatype parameter values, both from media_type and media_type_param tables.
		$mediatype_sql = 'SELECT type, status, maxsessions, maxattempts, attempt_interval, content_type, script, '.
			'timeout, process_tags, show_event_menu, event_menu_name, event_menu_url, description, mtp.name, mtp.value '.
			'FROM media_type mt INNER JOIN media_type_param mtp ON mt.mediatypeid=mtp.mediatypeid WHERE mt.name=';
		$old_hash = CDBHelper::getHash($mediatype_sql.'\'Reference webhook\'');

		// Clone the reference media type.
		$this->page->login()->open('zabbix.php?action=mediatype.list');
		$this->query('link:Reference webhook')->one()->WaitUntilClickable()->click();
		$this->query('button:Clone')->one()->click();
		$form = $this->query('id:media_type_form')->asForm()->waitUntilVisible()->one();
		$form->fill(['Name' => 'Webhook clone']);
		$form->submit();
		$this->page->waitUntilReady();

		$message = CMessageElement::find()->one();
		$this->assertTrue($message->isGood());
		$this->assertEquals('Media type added', $message->getTitle());
		// Check that the parameters of the clone and of the cloned media types are equal.
		$this->assertEquals($old_hash, CDBHelper::getHash($mediatype_sql.'\'Webhook clone\''));
	}

	public function testFormAdministrationMediaTypeWebhook_Cancel() {
		$fields = [
			'Name' => 'To be Cancelled',
			'Type' => 'Webhook',
			'Script' => '2 B Cancelled'
		];
		foreach (['creation', 'update', 'clone'] as $action) {
			$old_hash = CDBHelper::getHash($this->sql);

			$this->page->login()->open('zabbix.php?action=mediatype.list');
			$button = ($action === 'creation') ? 'button:Create media type' : 'link:Reference webhook';
			$this->query($button)->one()->WaitUntilClickable()->click();
			if ($action === 'clone') {
				$this->query('button:Clone')->one()->click();
			}
			$form = $this->query('id:media_type_form')->asForm()->waitUntilVisible()->one();
			$form->fill($fields);
			$form->query('button:Cancel')->one()->click();
			$this->page->waitUntilReady();
			// Make sure no changes took place.
			$this->assertEquals($old_hash, CDBHelper::getHash($this->sql));
		}
	}

	public function testFormAdministrationMediaTypeWebhook_Delete() {
		$mediatypeid = CDBHelper::getValue('SELECT mediatypeid FROM media_type WHERE name=\'Webhook to delete\'');
		$this->page->login()->open('zabbix.php?action=mediatype.edit&mediatypeid='.$mediatypeid);
		$this->query('button:Delete')->one()->waitUntilClickable()->click();
		$this->page->acceptAlert();
		$this->page->waitUntilReady();
		// Verify that media type was deleted
		$message = CMessageElement::find()->one();
		$this->assertTrue($message->isGood());
		$this->assertEquals('Media type deleted', $message->getTitle());
		$this->assertEquals(0, CDBHelper::getCount('SELECT mediatypeid FROM media_type WHERE name=\'Webhook to delete\''));
	}

	// Finction that removes existing webhook parameters or adds new ones based on the Action field in data provider.
	private function fillParametersField($data) {
		$parameters_table = $this->query('id:parameters_table')->asTable()->one();
		foreach ($data['parameters'] as $parameter) {
			if (CTestArrayHelper::get($parameter, 'Action', 'Add') === 'Remove') {
				$delete_row = $parameters_table->getRow($parameter['Name']);
				$delete_row->query('button:Remove')->one()->waitUntilClickable()->click();
			}
			else {
				$rows_count = $parameters_table->getRows()->count();
				$parameters_table->query('button:Add')->one()->click();
				// Name is placed in the 1st td element of each row, and value - in the 2nd td element.
				$new_name = $parameters_table->query('xpath://tbody/tr['.$rows_count.']/td[1]/input')->one();
				$new_value = $parameters_table->query('xpath://tbody/tr['.$rows_count.']/td[2]/input')->one();
				$new_name->fill($parameter['Name']);
				$new_value->fill($parameter['Value']);
			}
		}
	}

	/*
	 * Function used to popullate fields located in the Operations tab.
	 * Field concurrent sessions has two input elelents - one of them is displayed only if concurrent sessions = Custom.
	 * Therefore, fill() method cannot be used for this field, and it needs to be popullated separately.
	 */
	private function fillOperationsTab($data, $form) {
		if (CTestArrayHelper::get($data, 'options', false)) {
			$form->fill($data['options']);
		}
		if (CTestArrayHelper::get($data, 'concurrent_sessions', false)) {
			$container = $form->getFieldContainer('Concurrent sessions');
			if (is_array($data['concurrent_sessions'])) {
				$container->query('id:maxsessions_type')->asSegmentedRadio()->one()->select(array_key_first($data['concurrent_sessions']));
				$container->query('id:maxsessions')->one()->fill(array_values($data['concurrent_sessions']));
			}
			else {
				$container->query('id:maxsessions_type')->asSegmentedRadio()->one()->select($data['concurrent_sessions']);
			}
		}
	}

	/*
	 * Function that checks the displayed message and checks created/updated media type in dB,
	 * or, in case of a negative scenario, checks error message title and details.
	 */
	private function assertMediaTypeMessage($data, $good_title, $bad_title) {
		$message = CMessageElement::find()->one();
		switch ($data['expected']) {
			case TEST_GOOD:
				$this->assertTrue($message->isGood());
				$this->assertEquals($good_title, $message->getTitle());
				$mediatype_count = CDBHelper::getCount('SELECT mediatypeid FROM media_type WHERE name ='.zbx_dbstr($data['fields']['Name']));
				$count = ($good_title === 'Media type deleted') ? 0 : 1;
				$this->assertEquals($count, $mediatype_count);
				break;

			case TEST_BAD:
				$this->assertEquals($bad_title, $message->getTitle());
				$this->assertTrue($message->hasLine($data['error_message']));
				break;
		}
	}

	/*
	 * Check the field values after creating or updating a media type.
	 */
	private function checkMediaTypeFields($data) {
		$mediatypeid = CDBHelper::getValue('SELECT mediatypeid FROM media_type WHERE name ='.zbx_dbstr($data['fields']['Name']));
		$this->page->open('zabbix.php?action=mediatype.edit&mediatypeid='.$mediatypeid);
		$form = $this->query('id:media_type_form')->asForm()->waitUntilVisible()->one();

		// Check that fields in Media type tab are updated.
		$check_media_fields = ['Name', 'Type', 'Script', 'Timeout', 'Process tags', 'Include event menu entry',
				'Menu entry name', 'Menu entry URL', 'Description', 'Enabled'];
		foreach ($check_media_fields as $field_name) {
			if (array_key_exists($field_name, $data['fields'])) {
				$this->assertEquals($data['fields'][$field_name], $form->getField($field_name)->getValue());
			}
		}
		// Check that webhook parameters are added / removed, or check that all 4 default prameters are present.
		$params_table = $form->getField('Parameters')->asTable();
		if (CTestArrayHelper::get($data, 'parameters', false)) {
			foreach ($data['parameters'] as $parameter) {
				if (CTestArrayHelper::get($parameter, 'Action', 'Add') === 'Remove') {
					$this->assertEquals($params_table->query('xpath://input[@value="'.$parameter['Name'].'"]')->count(), 0);
				}
				else {
					// Name is placed in the 1st td element of each row, and value - in the 2nd td element.
					$this->assertEquals($params_table->query('xpath://td[1]/input[@value="'.$parameter['Name'].'"]')->count(), 1);
					$this->assertEquals($params_table->query('xpath://td[2]/input[@value="'.$parameter['Value'].'"]')->count(), 1);
				}
			}
		}
		else {
			$default_params = [
				['Name' => 'URL', 'Value' => ''],
				['Name' => 'To', 'Value' => '{ALERT.SENDTO}'],
				['Name' => 'Subject', 'Value' => '{ALERT.SUBJECT}'],
				['Name' => 'Message', 'Value' => '{ALERT.MESSAGE}']
			];
			// Parameters table last row is occupied by the Add button, and should be deducted from the total row count.
			$this->assertEquals(count($default_params), $params_table->getRows()->count()-1);
			foreach($default_params as $parameter) {
				// Parameter name is placed in the 1st td element of each row, and parameter value - in the 2nd td element.
				$this->assertEquals($parameter['Name'], $params_table->query('xpath://td[1]/input[@value="'.
						$parameter['Name'].'"]')->one()->getValue());
				$this->assertEquals($parameter['Value'], $params_table->query('xpath://td[2]/input[@value="'.
						$parameter['Value'].'"]')->one()->getValue());
			}
		}
		// Check that fields in Options tab are updated.
		if (CTestArrayHelper::get($data, 'options', false) || CTestArrayHelper::get($data, 'concurrent_sessions', false)) {
			$check_options_fields = ['Concurrent sessions', 'Attempts', 'Attempt interval'];
			$form->selectTab('Options');
			foreach ($check_options_fields as $field_name) {
				// Field Concurrent sessions is checked separately as it may have 2 parameters to check (type and value).
				if ($field_name === 'Concurrent sessions' && CTestArrayHelper::get($data, 'concurrent_sessions', false)) {
					$container = $form->getFieldContainer('Concurrent sessions');
					// If concurrent sessions type is Custom then both type and value should be checked for this field.
					if (is_array($data['concurrent_sessions'])) {
						$this->assertEquals(array_key_first($data['concurrent_sessions']),
								$container->query('id:maxsessions_type')->asSegmentedRadio()->one()->getValue());
						$this->assertEquals($data['concurrent_sessions']['Custom'],
								$container->query('id:maxsessions')->one()->getValue());
					}
					else {
						// Only type needs to be checked if Concurrent sessions is set to One or Unlimited.
						$this->assertEquals($data['concurrent_sessions'],
								$container->query('id:maxsessions_type')->asSegmentedRadio()->one()->getValue());
					}
				}
				elseif (array_key_exists($field_name, $data['options'])) {
					$this->assertEquals($data['options'][$field_name], $form->getField($field_name)->getValue());
				}
			}
		}
		// Check that "Menu entry name" and "Menu entry URL" fields are enabled only if "Include event menu entry" is set.
		if (CTestArrayHelper::get($data, 'fields.Include event menu entry', false)) {
			$this->assertTrue($form->getField('Menu entry name')->isEnabled());
			$this->assertTrue($form->getField('Menu entry URL')->isEnabled());
		}
		else {
			$this->assertFalse($form->getField('Menu entry name')->isEnabled());
			$this->assertFalse($form->getField('Menu entry URL')->isEnabled());
		}
	}
}
