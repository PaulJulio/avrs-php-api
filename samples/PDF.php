<?php
namespace samples;
if (!(class_exists(__NAMESPACE__ . '\Loader'))) {
    require_once(realpath(__DIR__ . '/loader.php'));
}
use api\AVRSAPI as AVRSAPI;

$json   = json_encode(array(
    '_gte' => '-2 hours',
    '_lte' => 'now'
));
$url = '/api/v1/deals/?pdf=1&accept-time=' . urlencode($json);

$api = new AVRSAPI();
$api->enableDebug();
$api->setURL($url);
$api->send();
Writer::writeRequest($api);
if ($api->getInfo('http_code') == 200) {
    Writer::writeResponse($api, null, 'pdf');
} else {
    Writer::writeResponse($api, null, 'txt');
}
