<?php
namespace samples;
if (!(class_exists(__NAMESPACE__ . '\Loader'))) {
    require_once(realpath(__DIR__ . '/loader.php'));
}
use api;

$api = new api\AVRSAPI();
$api->setURL('/api/v1.5/apiauthkeys/');
$api->setMethod('POST');
$api->addPayload('passphrase', md5('this is my unhashed passphrase'));
$api->send();
Writer::writeRequestResponse($api);
$response = json_decode($api->getResult(),true);
$api->resetPayload();
$api->setMethod('DELETE');
$api->addPayload('id', $response['keys'][0]['id']);
$api->send();
