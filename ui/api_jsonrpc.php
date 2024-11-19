<?php
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


header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Max-Age: 1000');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	return;
}

require_once dirname(__FILE__).'/include/func.inc.php';
require_once dirname(__FILE__).'/include/classes/core/CHttpRequest.php';

$allowed_content = [
	'application/json-rpc' => 'json-rpc',
	'application/json' => 'json-rpc',
	'application/jsonrequest' => 'json-rpc'
];
$http_request = new CHttpRequest();
$content_type = $http_request->header('Content-Type');
$content_type = explode(';', $content_type);
$content_type = $content_type[0];

if (!isset($allowed_content[$content_type])) {
	header('HTTP/1.0 412 Precondition Failed');
	return;
}

require_once dirname(__FILE__).'/include/classes/core/APP.php';

header('Content-Type: application/json');
$data = $http_request->body();

try {
	APP::getInstance()->run(APP::EXEC_MODE_API);

	$apiClient = API::getWrapper()->getClient();

	// unset wrappers so that calls between methods would be made directly to the services
	API::setWrapper();

	$jsonRpc = new CJsonRpc($apiClient, $data);
	echo $jsonRpc->execute();
}
catch (Throwable $e) {
	$json_data = json_decode($data, true);

	if (array_key_exists('id', $json_data)) {
		$user_type = CUser::$userData === null ? USER_TYPE_ZABBIX_USER : CUser::$userData['type'];
		$data = ($e instanceof DBException && $user_type != USER_TYPE_SUPER_ADMIN && $e->getCode() != DB::INIT_ERROR)
			? _('System error occurred. Please contact Zabbix administrator.')
			: $e->getMessage();

		echo json_encode([
			'jsonrpc' => '2.0',
			'error' => [
				'code' => -32603,
				'message' => _('Internal error.'),
				'data' => $data
			],
			'id' => $json_data['id']
		]);
	}
}

session_write_close();
