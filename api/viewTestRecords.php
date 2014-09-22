<?php
namespace api;
if (!(class_exists(__NAMESPACE__ . '\Loader'))) {
	require_once(realpath(__DIR__ . '/loader.php'));
}

$api = new AVRSAPI();
$api->setURL('/api/v1/test-records/');
$api->enableDebug();
$api->send();
echo $api->getResult();
