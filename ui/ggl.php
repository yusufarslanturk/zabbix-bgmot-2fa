<?php
require_once dirname(__FILE__).'/include/config.inc.php';
require_once dirname(__FILE__).'/include/forms.inc.php';
include_once dirname(__FILE__).'/gglauth/FixedBitNotation.php';
include_once dirname(__FILE__).'/gglauth/GoogleAuthenticatorInterface.php';
include_once dirname(__FILE__).'/gglauth/GoogleAuthenticator.php';
include_once dirname(__FILE__).'/gglauth/GoogleQrUrl.php';

$page['title'] = _('ZABBIX');
$page['file'] = 'ggl.php';

if (isset($_POST['code'])) {
	// Verify entered code and log in user.
	// Find current user's secret
	$db_users = DB::select('users', [
		'output' => ['userid', 'alias', 'ggl_secret', 'ggl_enrolled'],
		'filter' => ['alias' => $_POST['name']]
	]);
	if (count($db_users) == 0) {
		// Should never be the case as we get here only after regular auth is done
		CWebUser::logout();
		redirect('index.php');
		exit;
	}
	$secret = $db_users[0]['ggl_secret'];

	// Verify code
	$g = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();
	if ($g->checkCode($secret, $_POST['code'])) {
		// Verification successful
		// Update database if this is the very fist 2FA authentication
		CWebUser::setSessionCookie($_POST['sessionid']);
		if (!zbx_empty(CWebUser::$data['url'])) {
			$url = CWebUser::$data['url'];
		}
		else {
			$url = ZBX_DEFAULT_URL;
		}
		if ($db_users[0]['ggl_enrolled'] == 0) {
			unset($db_users[0]['ggl_secret']);
			$db_users[0]['ggl_enrolled'] = 1;
			API::User()->update($db_users);
		}
		redirect($url);
		exit;
	}
	else {
		// Verification failed
		$data = [
			'sessionid' => $_POST['sessionid'],
			'name' => $_POST['name'],
			'error' => 'The code provided is incorrect.'
		];
		echo (new CView('general.ggl', $data))->getOutput();
		exit;
	}
}

$name = CWebUser::$data['alias'];
if (!$name || $name == ZBX_GUEST_USER) {
  // User is not authenticated
  redirect('index.php');
}

// Authentication is not complete yet so reset cookie
// to make it impossible to visit other pages until 2FA complete
zbx_unsetcookie('zbx_sessionid');

// Find out whether the user is already enrolled in Google Authenticator
$db_users = DB::select('users', [
	'output' => ['userid', 'alias', 'ggl_secret', 'ggl_enrolled'],
	'filter' => ['alias' => $name]
]);
if (count($db_users) == 0) {
	// Should never be the case as we get here only after regular auth is done
	CWebUser::logout();
	redirect('index.php');
	exit;
}

if ($db_users[0]['ggl_enrolled'] == 0) {
	// Enrollment was not ever started or completed properly
	$g = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();
	if ($db_users[0]['ggl_secret'] == '') {
		$secret= $g->generateSecret();
		$db_users[0]['ggl_secret'] = $secret;
		// Store secret in database
		API::User()->update($db_users);
	}
	// Get QR code image
	$qr_url = \Sonata\GoogleAuthenticator\GoogleQrUrl::generate($name, $db_users[0]['ggl_secret'], $ZBX_SERVER_NAME);
}
// Authentication is not complete yet so reset cookie
// to make it impossible to visit other pages until 2FA complete
$data = [
	'sessionid' => CWebUser::$data['sessionid'],
	'name' => $name,
	'qr_url' => $qr_url,
	'error' => $error
];
//CSessionHelper::clear();

echo (new CView('general.ggl', $data))
	->getOutput();
?>
