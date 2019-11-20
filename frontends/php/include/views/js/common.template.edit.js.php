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
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


// TODO VM: do we really need this readonly check?
if (!$data['readonly']) {
	?>
	<script type="text/x-jquery-tmpl" id="macro-row-tmpl-inherited">
		<?= (new CRow([
				(new CCol([
					(new CTextAreaFlexible('macros[#{rowNum}][macro]', '', ['add_post_js' => false]))
						->addClass('macro')
						->setWidth(ZBX_TEXTAREA_MACRO_WIDTH)
						->setAttribute('placeholder', '{$MACRO}'),
					new CInput('hidden', 'macros[#{rowNum}][type]', ZBX_PROPERTY_OWN)
				]))->addClass(ZBX_STYLE_TEXTAREA_FLEXIBLE_PARENT),
				'&rArr;',
				(new CCol(
					(new CTextAreaFlexible('macros[#{rowNum}][value]', '', ['add_post_js' => false]))
						->setWidth(ZBX_TEXTAREA_MACRO_VALUE_WIDTH)
						->setAttribute('placeholder', _('value'))
				))->addClass(ZBX_STYLE_TEXTAREA_FLEXIBLE_PARENT),
				(new CCol(
					(new CButton('macros[#{rowNum}][remove]', _('Remove')))
						->addClass(ZBX_STYLE_BTN_LINK)
						->addClass('element-table-remove')
				))->addClass(ZBX_STYLE_NOWRAP),
				[
					new CCol(
						(new CDiv())
							->addClass(ZBX_STYLE_OVERFLOW_ELLIPSIS)
							->setWidth(ZBX_TEXTAREA_MACRO_VALUE_WIDTH)
					),
					new CCol(),
					new CCol(
						(new CDiv())
							->addClass(ZBX_STYLE_OVERFLOW_ELLIPSIS)
							->setWidth(ZBX_TEXTAREA_MACRO_VALUE_WIDTH)
					)
				]
			]))
				->addClass('form_row')
				->toString().
			(new CRow([
				(new CCol(
					(new CTextAreaFlexible('macros[#{rowNum}][description]', '', ['add_post_js' => false]))
						->setMaxlength(DB::getFieldLength('globalmacro', 'description'))
						->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
						->setAttribute('placeholder', _('description'))
				))->addClass(ZBX_STYLE_TEXTAREA_FLEXIBLE_PARENT)->setColSpan(8),
			]))
				->addClass('form_row')
				->toString()
		?>
	</script>
	<script type="text/x-jquery-tmpl" id="macro-row-tmpl">
		<?= (new CRow([
				(new CCol([
					(new CTextAreaFlexible('macros[#{rowNum}][macro]', '', ['add_post_js' => false]))
						->addClass('macro')
						->setWidth(ZBX_TEXTAREA_MACRO_WIDTH)
						->setAttribute('placeholder', '{$MACRO}')
				]))->addClass(ZBX_STYLE_TEXTAREA_FLEXIBLE_PARENT),
				'&rArr;',
				(new CCol(
					(new CTextAreaFlexible('macros[#{rowNum}][value]', '', ['add_post_js' => false]))
						->setWidth(ZBX_TEXTAREA_MACRO_VALUE_WIDTH)
						->setAttribute('placeholder', _('value'))
				))->addClass(ZBX_STYLE_TEXTAREA_FLEXIBLE_PARENT),
				(new CCol(
					(new CTextAreaFlexible('macros[#{rowNum}][description]', '', ['add_post_js' => false]))
						->setMaxlength(DB::getFieldLength('globalmacro', 'description'))
						->setWidth(ZBX_TEXTAREA_MACRO_VALUE_WIDTH)
						->setAttribute('placeholder', _('description'))
				))->addClass(ZBX_STYLE_TEXTAREA_FLEXIBLE_PARENT),
				(new CCol(
					(new CButton('macros[#{rowNum}][remove]', _('Remove')))
						->addClass(ZBX_STYLE_BTN_LINK)
						->addClass('element-table-remove')
				))->addClass(ZBX_STYLE_NOWRAP)
			]))
				->addClass('form_row')
				->toString()
		?>
	</script>
	<script type="text/javascript">
		function processHostMacrosListTable(show_inherited_macros) {
			/*
			 * If show_inherited_macros comes as string for some reason, convert it to number and then to boolean.
			 * Otherwise "0" converts to true.
			 * https://www.w3schools.com/js/tryit.asp?filename=tryjs_type_convert_string_0
			 */
			show_inherited_macros = Boolean(Number(show_inherited_macros));

			jQuery('#tbl_macros')
				.dynamicRows({
					remove_next_sibling: show_inherited_macros,
					template: show_inherited_macros ? '#macro-row-tmpl-inherited' : '#macro-row-tmpl'
				})
				.on('blur', '.<?= ZBX_STYLE_TEXTAREA_FLEXIBLE ?>', function() {
					if (jQuery(this).hasClass('macro')) {
						macroToUpperCase(this);
					}
					jQuery(this).trigger('input');
				})
				.on('click', 'button.element-table-add', function() {
					jQuery('#tbl_macros .<?= ZBX_STYLE_TEXTAREA_FLEXIBLE ?>').textareaFlexible();
				})
				.on('click', 'button.element-table-change', function() {
					var macroNum = jQuery(this).attr('id').split('_')[1];

					if (jQuery('#macros_' + macroNum + '_type').val() & <?= ZBX_PROPERTY_OWN ?>) {
						jQuery('#macros_' + macroNum + '_type')
							.val(jQuery('#macros_' + macroNum + '_type').val() & (~<?= ZBX_PROPERTY_OWN ?>));
						jQuery('#macros_' + macroNum + '_value')
							.prop('readonly', true)
							.val(jQuery('#macros_' + macroNum + '_inherited_value').val());
						jQuery('#macros_' + macroNum + '_description')
							.prop('readonly', true)
							.val(jQuery('#macros_' + macroNum + '_inherited_description').val());
						jQuery('#macros_' + macroNum + '_change')
							.text(<?= CJs::encodeJson(_x('Change', 'verb')) ?>);
					}
					else {
						jQuery('#macros_' + macroNum + '_type')
							.val(jQuery('#macros_' + macroNum + '_type').val() | <?= ZBX_PROPERTY_OWN ?>);
						jQuery('#macros_' + macroNum + '_value')
							.prop('readonly', false)
							.focus();
						jQuery('#macros_' + macroNum + '_description')
							.prop('readonly', false);
						jQuery('#macros_' + macroNum + '_change')
							.text(<?= CJs::encodeJson(_('Remove')) ?>);
					}
				});

			jQuery('form[name="hostsForm"], form[name="templatesForm"]').submit(function() {
				jQuery('input.macro').each(function() {
					macroToUpperCase(this);
				});
			});
		}

		function macroToUpperCase(element) {
			var macro = jQuery(element).val(),
				end = macro.indexOf(':');

			if (end == -1) {
				jQuery(element).val(macro.toUpperCase());
			}
			else {
				var macro_part = macro.substr(0, end),
					context_part = macro.substr(end, macro.length);

				jQuery(element).val(macro_part.toUpperCase() + context_part);
			}
		}
	</script>
	<?php
}
	?>
	<script type="text/javascript">
		/**
		 * Collects IDs selected in "Add templates" multiselect.
		 *
		 * @returns {Array|getAddTemplates.templateids}
		*/
		function getAddTemplates($ms) {
			var templateids = [];

			// Readonly forms don't have multiselect.
			if ($ms.length) {
				// Collect IDs from Multiselect.
				$ms.multiSelect('getData').forEach(function(template) {
					templateids.push(template.id);
				});
			}

			return templateids;
		}

		/**
		 * Get macros from Macros tab form
		 *
		 * @param {jQuery} $form  jQuery object for host edit form
		 * @returns {Array}       list of all host macros in the form
		 */
		function getMacros($form) {
			var $macros = $form.find('input[name^="macros"], textarea[name^="macros"]'),
				macros = [];

			// Find the correct macro inputs and prepare to submit them via AJAX. matches[1] - index, matches[2] field name.
			$macros.each(function() {
				var $this = jQuery(this),
					matches = $this.attr('name').match(/macros\[(\d+)\]\[(\w+)\]/);

				if (typeof macros[matches[1]] === 'undefined') {
					macros[matches[1]] = new Object();
				}

				macros[matches[1]][matches[2]] = $this.val();
			});

			// Some rows may have been removed, but JS likes to create empty indexes. Avoid that by cleaning the array.
			macros.clean(undefined);

			// TODO VM: why on empty array values are not cleared?
			// TODO VM: why on many values only one is remembered?
			return macros;
		}

		jQuery(function($) {
			// TODO VM: (bug) value textareas are expanding when new value is added.
			var $container = $('#macros_container .table-forms-td-right'),
				$ms = $('#add_templates_'),
				$show_inherited_macros = $('input[name="show_inherited_macros"]'),
				$form = $show_inherited_macros.closest('form'),
				add_templates = <?= CJs::encodeJson($data['macros_tab']['add_templates']) ?>,
				readonly = <?= (int) $data['readonly'] ?>;

			if (readonly === 0) {
				processHostMacrosListTable(<?= (int) $data['show_inherited_macros'] ?>);
			}

			$('#tabs').on('tabsactivate', function(event, ui) {
				if (ui.newPanel.attr('id') === 'macroTab') {
					var add_templates_tmp = getAddTemplates($ms);

					if (add_templates.diff(add_templates_tmp).length > 0) {
						add_templates = add_templates_tmp;
						$show_inherited_macros.trigger('change');
					}
				}
			});

			$show_inherited_macros.on('change', function() {
				if (!$(this).is(':checked')) {
					return;
				}

				var url = new Curl('zabbix.php'),
					show_inherited_macros_value = $(this).val();

				url.setArgument('action', 'hostmacros.list');

				$.ajax({
					data: {
						macros: getMacros($form),
						show_inherited_macros: show_inherited_macros_value,
						templateids: <?= CJs::encodeJson($data['macros_tab']['linked_templates']) ?>,
						add_templates: add_templates,
						readonly: readonly
					},
					url: url.getUrl(),
					dataType: 'json',
					method: 'POST',
					beforeSend: function() {
						$container.empty().append(
							$('<span></span>').addClass('preloader').css({'display': 'inline-block'})
						);

						// DEV-1276 replace with this: $container.addClass('is-loading');
					},
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
							$msg_box = $('main').find('>.msg-good, >.msg-bad'),
							$list = $('<ul>').addClass('msg-details-border'),
							$msg_details = $('<div>')
								.addClass('msg-details')
								.append($list),
							$details_arrow = $('<span>')
								.attr('id', 'details-arrow')
								.addClass('arrow-up'),
							$link_details = $('<a>')
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
							showHide($(this)
								.siblings('.msg-details')
								.find('.msg-details-border')
							);
							$('#details-arrow', $(this)).toggleClass('arrow-up arrow-down');
							$(this).attr('aria-expanded', $(this)
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
								var $details = $($msg_box).find('.msg-details-border');

								if ($details.length > 0) {
									// Append loaded message list (<li> elements) to existing details box list.
									$details.append($(errors).find('.msg-details ul li'));
								}
								else {
									/*
									 * If there is no details box, just the title, create a new details box with link and arrow
									 * and append the message list (<li> elements) to it.
									 */
									$list.append($(errors).find('.msg-details ul li'));
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
								$(errors).find('.msg-details ul li').each(function() {
									messages.push($(this).text());
								});

								$('main').prepend(makeMessageBox('bad', messages, t('Cannot load macros'), true, true));
							}
						}
						else {
							if (typeof response.messages !== 'undefined') {
								/*
								 * If message box (with or without details exists) add messages to it. If not, create new message
								 * box without title because those runtime errors like undefined indexes don't have a title.
								 */
								if ($msg_box.length > 0) {
									// The details box may not exist on page.
									var $details = $($msg_box).find('.msg-details-border');

									if ($details.length > 0) {
										// Append loaded message list (<li> elements) to existing details box list.
										$details.append($(response.messages).find('.msg-details ul li'));
									}
									else {
										/*
										 * If there is no details box, just the title, create a new details box with link and arrow
										 * and append the message list (<li> elements) to it.
										 */
										$list.append($(response.messages).find('.msg-details ul li'));
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
									$(response.messages).find('.msg-details ul li').each(function() {
										messages.push($(this).text());
									});
									$('main').prepend(makeMessageBox('bad', messages));
								}
							}


							$container.empty().append(response.body);

							// TODO VM: change to full PHP if
							if (<?= (int) $data['readonly'] ?> == 0) {
								processHostMacrosListTable(show_inherited_macros_value);
							}

							// Display debug after loaded content if it is enabled for user.
							if (typeof response.debug !== 'undefined') {
								$container.append(response.debug);

								// Stylize the debug with corret margin inside the block.
								$('.debug-output', $container).css({margin: '10px 13px 0 0'});
							}
						}
					},
					complete: function() {
						// Due to possible errors, the loader may stay on page. Remove it once request has been completed.
						$container.find('.preloader').remove();

						// DEV-1276 replace with this: $container.removeClass('is-loading');
					}
				});
			});
		});
	</script>

