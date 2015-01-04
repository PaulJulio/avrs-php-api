<?php
namespace samples;
if (!(class_exists(__NAMESPACE__ . '\Loader'))) {
    require_once(realpath(__DIR__ . '/loader.php'));
}
use api\AVRSAPI as AVRSAPI;

$api = new AVRSAPI();
$api->enableDebug();
$api->setURL('/api/v1/deals/?pdf=1');
$api->send();
Writer::writeRequest($api);
if ($api->getInfo('code') == 200) {
    Writer::writeResponse($api, null, 'pdf');
} else {
    Writer::writeResponse($api, null, 'txt');
}
