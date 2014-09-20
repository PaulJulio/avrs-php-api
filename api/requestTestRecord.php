<?php
namespace api;
if (!(class_exists(__NAMESPACE__ . '\Loader'))) {
	require_once(realpath(__DIR__ . '/loader.php'));
}

$api = new AVRSAPI();
$api->setURL('/api/v1/test-records/');
$api->setMethod('POST');
// See /api/v1/test-records/conditions/ to view a list of bits to set for various inventory types
$api->addPayload('conditions', 134217729);
$api->send();
echo $api->getResult();
