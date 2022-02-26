<?php
/**
 * @var CView $this
 */
?>

<script type="text/javascript">
	jQuery(function($) {
		var $form = $('[name=twofaForm]'),
			warn = true;

		$form.submit(function() {
			var proceed = !warn
				|| $('[name=2fa_type]:checked').val() == $('[name=db_2fa_type]').val()
				|| confirm(<?= json_encode(
					_('Switching two factor authentication method will reset all except this session! Continue?')
				) ?>);
			warn = true;

			return proceed;
		});
	});
</script>
