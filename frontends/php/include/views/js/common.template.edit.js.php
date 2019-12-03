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

				// Must execute flexible textarea script when macros list is loaded for the first time.
				jQuery('#tbl_macros .<?= ZBX_STYLE_TEXTAREA_FLEXIBLE ?>').textareaFlexible();

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
	 * @param {jQuery} $ms  jQuery object of multiselect.
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
	 * Get macros from Macros tab form.
	 *
	 * @param {jQuery} $form  jQuery object for host edit form.
	 *
	 * @return {Array}        List of all host macros in the form.
	 */
	function getMacros($form) {
		var $macros = $form.find('input[name^="macros"], textarea[name^="macros"]'),
			macros = {};

		// Find the correct macro inputs and prepare to submit them via AJAX. matches[1] - index, matches[2] field name.
		$macros.each(function() {
			var $this = jQuery(this),
				matches = $this.attr('name').match(/macros\[(\d+)\]\[(\w+)\]/);

			if (!macros.hasOwnProperty(matches[1])) {
				macros[matches[1]] = new Object();
			}

			macros[matches[1]][matches[2]] = $this.val();
		});

		return macros;
	}

	jQuery(function($) {
		var $container = $('#macros_container .table-forms-td-right'),
			$ms = $('#add_templates_'),
			$show_inherited_macros = $('input[name="show_inherited_macros"]'),
			$form = $show_inherited_macros.closest('form'),
			linked_templates = <?= CJs::encodeJson($data['macros_tab']['linked_templates']) ?>,
			add_templates = <?= CJs::encodeJson($data['macros_tab']['add_templates']) ?>,
			readonly = <?= (int) $data['readonly'] ?>;

		if (!readonly) {
			processHostMacrosListTable($show_inherited_macros.val() == 1);
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
				macros = getMacros($form),
				show_inherited_macros = $(this).val() == 1;

			url.setArgument('action', 'hostmacros.list');

			// DEV-1276 replace with this: $container.addClass('is-loading');
			$container.empty().append(
				$('<span></span>').addClass('preloader').css({'display': 'inline-block'})
			);

			$.ajax(url.getUrl(), {
				data: {
					macros: macros,
					show_inherited_macros: show_inherited_macros ? 1 : 0,
					templateids: linked_templates.concat(add_templates),
					readonly: readonly
				},
				dataType: 'json',
				method: 'POST'
			})
				.done(function(response) {
					if (typeof response === 'object' && 'errors' in response) {
						$container.append(response.errors);
					}
					else {
						if (typeof response.messages !== 'undefined') {
							$container.append(response.messages);
						}

						$container.append(response.body);

						if (!readonly) {
							processHostMacrosListTable(show_inherited_macros);
						}

						// Display debug after loaded content if it is enabled for user.
						if (typeof response.debug !== 'undefined') {
							$container.append(response.debug);

							// Override margin for inline usage.
							$('.debug-output', $container).css('margin', '10px 0');
						}
					}
				})
				.always(function() {
					// DEV-1276 replace with this: $container.removeClass('is-loading');
					$container.find('.preloader').remove();
				});
		});
	});
</script>
