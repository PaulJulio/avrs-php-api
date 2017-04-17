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
$api->setURL('/api/v1.5/deals/');
$api->setMethod('POST');
$dealData = [
    'owners' => [['zip' => 95492]],
    'vehicles' => [[
                        'cost'                => 45000,
                        'first-operated-date' => '2017-11-11',
                        'first-sold-date'     => '2017-11-11',
                        'vin'                 => 'JH4KA8250MC004002',
                        'type-license-code'   => 11,
                    ]],
    'transaction-type' => 3,
    'gateway-type' => 'CALC-CA',
];
$api->addPayload('deals', [$dealData]);
$api->send();
$response = json_decode($api->getResult(), true);
Writer::writeRequestResponse($api);
$api = new AVRSAPI();
$api->setURL('/api/v1.5/deals/transactions/');
$api->setMethod('POST');
$api->addPayload('deal-status', 'FR');
$api->addPayload('deal-id', $response['deals'][0]['id']);
$api->send();
$response = json_decode($api->getResult(), true);
while ($retryAttempts++ < $retryMax && isset($response['error']) && $response['error']['code'] == 'CADMV/Q023') {
    error_log('DMV Retry Code Encountered');
    sleep($retryDelayBase * pow(2, $retryAttempts));
    $api->send();
    $response = json_decode($api->getResult(), true);
}
Writer::writeRequestResponse($api);
