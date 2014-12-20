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
// See https://github.com/PaulJulio/avrs-php-api/blob/master/api/testrecords.php for the bit listing
$api->addPayload('conditions', api\TestRecords::BIT_AUTO | api\TestRecords::BIT_RENEWAL_DUE);
$api->send();
Writer::writeRequestResponse($api);
echo $api->getResult();
