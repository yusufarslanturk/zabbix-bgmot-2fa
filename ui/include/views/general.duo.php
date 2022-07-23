<?php

define('ZBX_PAGE_NO_HEADER', 1);
define('ZBX_PAGE_NO_FOOTER', 1);
define('ZBX_PAGE_NO_MENU', true);

require_once dirname(__FILE__).'/../page_header.php';

global $ZBX_SERVER_NAME;

$sessionid = $data['sessionid'];
$sig_request = $data['sig_request'];
$name = $data['name'];

$server = CTwofaHelper::get(CTwofaHelper::TWOFA_DUO_API_HOSTNAME);

(new CDiv([
	(new CTag('main', true, [
		(isset($ZBX_SERVER_NAME) && $ZBX_SERVER_NAME !== '')
			? (new CDiv($ZBX_SERVER_NAME))->addClass(ZBX_STYLE_SERVER_NAME)
			: null,
		(new CDiv([
			(new CDiv(makeLogo(LOGO_TYPE_NORMAL)))->addClass(ZBX_STYLE_SIGNIN_LOGO),
			(new CForm())
				->setId('duo_form')
				->cleanItems()
				->addVar('name', $name, 'name')
				->addVar('sessionid', $sessionid, 'sessionid'),
			(new CTag('script', true))
				->setAttribute('type', 'text/javascript')
				->setAttribute('src', 'js/Duo-Web-v2.js'),
			(new CTag('link', true))
				->setAttribute('href', 'assets/styles/Duo-Frame.css')
				->setAttribute('rel', 'stylesheet')
				->setAttribute('type', 'text/css'),
			(new CTag('iframe', true))
				->setAttribute('id', 'duo_iframe')
				->setAttribute('data-host', $server)
				->setAttribute('data-sig-request', $sig_request)
		]))->addClass(ZBX_STYLE_SIGNIN_CONTAINER),
		(new CDiv([
			(new CLink(_('Help'), CBrandHelper::getHelpUrl()))
				->setTarget('_blank')
				->addClass(ZBX_STYLE_GREY)
				->addClass(ZBX_STYLE_LINK_ALT),
			CBrandHelper::isRebranded() ? null : '&nbsp;&nbsp;•&nbsp;&nbsp;',
			CBrandHelper::isRebranded()
				? null
				: (new CLink(_('Support'), getSupportUrl()))
					->setTarget('_blank')
					->addClass(ZBX_STYLE_GREY)
					->addClass(ZBX_STYLE_LINK_ALT)
		]))->addClass(ZBX_STYLE_SIGNIN_LINKS)
	])),
	makePageFooter(false)
]))
	->addClass(ZBX_STYLE_LAYOUT_WRAPPER)
	->show();
?>
</body>
