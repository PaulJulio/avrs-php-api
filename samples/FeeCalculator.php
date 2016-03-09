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

// create a deal with the required fields and immediately request fees
$api = new AVRSAPI();
$api->setURL('/api/v1/deals/');
$api->setMethod('POST');
$api->addPayload('owners', [[
                                'street:0' => '123 Main St',
                                'city'     => 'WINDSOR',
                                'type'     => 'owner',
                                'zip'      => 95492,
                                'name:0'   => 'DOE JANE',
                                'dl-type'  => 'unlicensed',
                            ]]);
$api->addPayload('vehicles', [[
                                  'cost'                => 45000,
                                  'first-operated-date' => '2017-11-11',
                                  'first-sold-date'     => '2017-11-11',
                                  'odometer'            => 11,
                                  'odometer-code'       => 'actual',
                                  'odometer-unit'       => 'M',
                                  'vin'                 => '3B7HC13YXYG105749',
                                  // following values can be retrieved from the VIN API
                                  'fuel-type'           => 'F',
                                  'make'                => 'GMC',
                                  'model-body'          => 'SD',
                                  'model-year'          => 2015,
                                  'type-license-code'   => 11,

                              ]]);
$api->addPayload('attributes', 4); // no lien holder
$api->addPayload('dealer-number', 12345);
$api->addPayload('status', 'QF');
$api->addPayload('transaction-type', 3);
$api->send();
$response = json_decode($api->getResult(), true);
while ($retryAttempts++ < $retryMax && $response['deals'][0]['error-code'] == 'CADMV/Q023') {
    error_log('DMV Retry Code Encountered');
    sleep($retryDelayBase * pow(2, $retryAttempts));
    $api->send();
    $response = json_decode($api->getResult(), true);
}
Writer::writeRequestResponse($api);
