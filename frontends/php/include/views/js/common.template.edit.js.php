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
						->setMaxlength(DB::getFieldLength('globalmacro' , 'description'))
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
						->setMaxlength(DB::getFieldLength('globalmacro' , 'description'))
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
