<?php
namespace samples;
if (!(class_exists(__NAMESPACE__ . '\Loader'))) {
    require_once(realpath(__DIR__ . '/loader.php'));
}
use api\AVRSAPI as AVRSAPI;
use api\TestRecords as TestRecords;

// exponential backoff settings
$retryAttempts  = 0;
$retryMax       = 3;
$retryDelayBase = 3;

// create a deal with the required fields, request fees after getting a deal with decoded vin info back
$api = new AVRSAPI();
$api->setURL('/api/v1/deals/');
$api->setMethod('POST');
$api->addPayload('owners', [[
                                'zip' => 95492,
                            ]]);
$api->addPayload('vehicles', [[
                                  'cost'                => 45000,
                                  'first-operated-date' => '2017-11-11',
                                  'first-sold-date'     => '2017-11-11',
                                  'vin'                 => '3B7HC13YXYG105749',
                                  'type-license-code'   => 11,

                              ]]);
$api->addPayload('transaction-type', 3);
$api->addPayload('gateway-type', 'CALC-CA');
$api->send();
$response = json_decode($api->getResult(), true);
Writer::writeRequestResponse($api);
$api = new AVRSAPI();
$api->setURL('/api/v1/deals/');
$api->setMethod('PUT');
$api->addPayload('status', 'QF');
$api->addPayload('id', $response['deals'][0]['id']);
$api->send();
$response = json_decode($api->getResult(), true);
while ($retryAttempts++ < $retryMax && $response['deals'][0]['error-code'] == 'CADMV/Q023') {
    error_log('DMV Retry Code Encountered');
    sleep($retryDelayBase * pow(2, $retryAttempts));
    $api->send();
    $response = json_decode($api->getResult(), true);
}
Writer::writeRequestResponse($api);
