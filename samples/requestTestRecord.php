<?php
namespace samples;
if (!(class_exists(__NAMESPACE__ . '\Loader'))) {
	require_once(realpath(__DIR__ . '/loader.php'));
}
use api;

$api = new api\AVRSAPI();
$api->setURL('/api/v1/test-records/');
$api->setMethod('POST');
$api->enableDebug();
// See /api/v1/test-records/conditions/ to view a list of bits to set for various inventory types
$api->addPayload('conditions', 134217729);
$api->send();
Writer::writeRequestResponse($api);
echo $api->getResult();
