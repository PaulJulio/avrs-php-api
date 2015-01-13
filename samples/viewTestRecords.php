<?php
namespace samples;
if (!(class_exists(__NAMESPACE__ . '\Loader'))) {
	require_once(realpath(__DIR__ . '/loader.php'));
}
use api;

$api = new api\AVRSAPI();
$api->setURL('/api/v1/test-records/');
$api->send();
Writer::writeRequestResponse($api);
var_export(json_decode($api->getResult(),true));
