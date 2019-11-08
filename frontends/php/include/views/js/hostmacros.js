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


/**
 * Find macro tab, collect multiselect IDs and perform AJAX request if IDs have changed since page was last submitted.
 *
 * @param {number} tab_idx  Tab index from session storage.
 */
hostmacros.tabChange = function(tab_idx) {
	var $this = this;

	// Find the correct macro tab and perform AJAX request to get the macro list.
	jQuery('#tabs a').each(function(i) {
		if (tab_idx == i && jQuery(this).attr('id') === 'tab_' + $this.data.tab_id) {
			var $ms = jQuery('#' + $this.data.form_id).find('#' + $this.data.ms_id),
				templateids = [];

			// Readonly forms don't have multiselect.
			if ($ms.length) {
				// Collect IDs from Multiselect.
				$ms.multiSelect('getData').forEach(function(template) {
					templateids.push(template.id);
				});
			}

			/*
			 * Check if Multiselect has changed the values and replace them. Note that IDs will be added as strings.
			 * if there is no multiselect, IDs have not changed and no need to reload.
			 */
			if ($this.data.add_templates.diff(templateids).length > 0) {
				$this.data.add_templates = templateids;

				$this.loadMacros();
			}
		}
	});
};

/**
 * AJAX request to load the macro list depeding on given data.
 */
hostmacros.loadMacros = function() {
	var $this = this,
		url = new Curl('zabbix.php');

	url.setArgument('action', 'hostmacros.list');

	jQuery.ajax({
		data: $this.data,
		url: url.getUrl(),
		dataType: 'json',
		method: 'POST',
		success: function(response) {
			/*
			 * Be mindful of ZBX-16148 (ZBXNEXT-5538). It may implement new functions for messages:
			 *   - addMessage()
			 *   - removeMessages()
			 */

			/*
			 * Create message box parts to display only errors by using objects similar to ones already
			 * in makeMessageBox() function.
			 */
			var errors = [],
				$msg_box = jQuery('main').find('>.msg-good, >.msg-bad'),
				$list = jQuery('<ul>').addClass('msg-details-border'),
				$msg_details = jQuery('<div>')
					.addClass('msg-details')
					.append($list),
				$details_arrow = jQuery('<span>')
					.attr('id', 'details-arrow')
					.addClass('arrow-up'),
				$link_details = jQuery('<a>')
					.text(t('Details') + ' ')
					.addClass('link-action')
					.attr('href', 'javascript:void(0)')
					.attr('role', 'button')
					.append($details_arrow)
					.attr('aria-expanded', 'true');

			if (typeof response === 'object' && 'errors' in response) {
				errors = response.errors;
			}

			$link_details.click(function() {
				showHide(jQuery(this)
					.siblings('.msg-details')
					.find('.msg-details-border')
				);
				jQuery('#details-arrow', jQuery(this)).toggleClass('arrow-up arrow-down');
				jQuery(this).attr('aria-expanded', jQuery(this)
					.find('.arrow-down')
					.length == 0
				);
			});

			if (errors.length) {
				/*
				 * If message box exists (with or without details), add messages to it. If not, create new message
				 * box with title.
				 */
				if ($msg_box.length > 0) {
					// The details box may not exist on page.
					var $details = jQuery($msg_box).find('.msg-details-border');

					if ($details.length > 0) {
						// Append loaded message list (<li> elements) to existing details box list.
						$details.append(jQuery(errors).find('.msg-details ul li'));
					}
					else {
						/*
						 * If there is no details box, just the title, create a new details box with link and arrow
						 * and append the message list (<li> elements) to it.
						 */
						$list.append(jQuery(errors).find('.msg-details ul li'));
						$msg_box.prepend($link_details);
						$msg_box.append($msg_details);
					}
				}
				else {
					/*
					 * makeMessageBox() accepts messages as array or string, not object. Create a new message box with
					 * title and append what ever the <li> elements contain.
					 */
					var messages = [];
					jQuery(errors).find('.msg-details ul li').each(function() {
						messages.push(jQuery(this).text());
					});

					jQuery('main').prepend(makeMessageBox('bad', messages, t('Cannot load macros'), true, true));
				}
			}
			else {
				if (typeof response.messages !== undefined) {
					/*
					 * If message box (with or without details exists) add messages to it. If not, create new message
					 * box without title because those runtime errors like undefined indexes don't have a title.
					 */
					if ($msg_box.length > 0) {
						// The details box may not exist on page.
						var $details = jQuery($msg_box).find('.msg-details-border');

						if ($details.length > 0) {
							// Append loaded message list (<li> elements) to existing details box list.
							$details.append(jQuery(response.messages).find('.msg-details ul li'));
						}
						else {
							/*
							 * If there is no details box, just the title, create a new details box with link and arrow
							 * and append the message list (<li> elements) to it.
							 */
							$list.append(jQuery(response.messages).find('.msg-details ul li'));
							$msg_box.prepend($link_details);
							$msg_box.append($msg_details);
						}
					}
					else {
						/*
						 * Runtime erros don't have a title for message box.
						 * makeMessageBox() accepts messages as array or string, not object.
						 */
						var messages = [];
						jQuery(response.messages).find('.msg-details ul li').each(function() {
							messages.push(jQuery(this).text());
						});
						jQuery('main').prepend(makeMessageBox('bad', messages));
					}
				}

				var $container = jQuery('#macros_container .table-forms-td-right');

				$container.empty().append(response.body);

				if ($this.data.readonly == 0) {
					processHostMacrosListTable($this.data.show_inherited_macros);
				}

				// Display debug after loaded content if it is enabled for user.
				if (typeof response.debug !== undefined) {
					$container.append(response.debug);

					// Stylize the debug with corret margin inside the block.
					jQuery('#macros_container .table-forms-td-right .debug-output').css({margin: '10px 13px 0 0'});
				}
			}
		},
		beforeSend: function() {
			jQuery('#macros_container .table-forms-td-right').empty().append(
				jQuery('<span></span>').addClass('preloader').css({'display': 'inline-block'})
			);

			// DEV-1276 replace with this: jQuery('#macros_container .table-forms-td-right').addClass('is-loading');
		},
		complete: function() {
			// Due to possible errors, the loader may stay on page. Remove it once request has been completed.
			jQuery('#macros_container .table-forms-td-right').find('.preloader').remove();

			// DEV-1276 replace with this: jQuery('#macros_container .table-forms-td-right').removeClass('is-loading');
		}
	});
};

jQuery(function($) {
	hostmacros.loadMacros();

	// Perform another AJAX request on radio button change.
	$('[name="show_inherited_macros"]').change(function() {
		var $this = $(this),
			$form = $this.closest('form'),
			$macros = $form.find('input[name^="macros"], textarea[name^="macros"]'),
			macros = [];

		// Store the changed radio button value for later use in processHostMacrosListTable() function.
		hostmacros.data.show_inherited_macros = $this.val();

		// Find the correct macro inputs and prepare to submit them via AJAX. matches[1] - index, matches[2] field name.
		$macros.each(function() {
			var matches = $(this).attr('name').match(/macros\[(\d+)\]\[(\w+)\]/);

			if (typeof macros[matches[1]] === 'undefined') {
				macros[matches[1]] = new Object();
			}

			macros[matches[1]][matches[2]] = $(this).val();
		});

		// Some rows may have been removed, but JS likes to create empty indexes. Avoid that by cleaning the array.
		macros.clean(undefined);

		hostmacros.data.macros = macros;

		hostmacros.loadMacros();
	});
});
