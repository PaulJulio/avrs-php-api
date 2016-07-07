<?php
namespace samples;
if (!(class_exists(__NAMESPACE__ . '\Loader'))) {
    require_once(realpath(__DIR__ . '/loader.php'));
}
use api\AVRSAPI;
use api\TestRecords;

$bitmask = TestRecords::BIT_AUTO;
// exponential backoff settings
$retryAttempts  = 0;
$retryMax       = 3;
$retryDelayBase = 3;

// do we have an inventory reservation already?
// check from newest reservation to oldest

$haveReservation = false;
$reservations    = array_reverse(TestRecords::getRecords());
foreach ($reservations as $reservation) {
    if (($reservation['conditions'] & $bitmask) == $bitmask) {
        $haveReservation = true;
        break;
    }
}
if (!$haveReservation) {
    $reservation = TestRecords::reserveRecord($bitmask);
}

// create a deal with the required fields and immediately request fees
$api = new AVRSAPI();
$api->setURL('/api/v1/deals/');
$api->setMethod('POST');
$api->addPayload('vehicles',
    [[
         'vin'                   => $reservation['vin'],
         'plate'                 => $reservation['plate'],
         'prior-owner-name'      => $reservation['owner'],
         'prior-lienholder-name' => $reservation['lien-holder'],
         'insurance'             => 'Y', // for testing environment only, certify that the vehicle is insured
         'smog'                  => 'CRT', // for testing environment only, declare Smog Cert In Hand
         'transfer-number'       => 1,
         'use-tax-reclass'       => 'Y',
         'ownership-cert-date'   => '2015-06-06',
         'odometer'              => 123456,
         'odometer-code'         => 'actual',
         'odometer-unit'         => 'M',
         'transfer-date'         => date('Y-m-d'),
         'cost'                  => 5000,
     ]]);
$api->addPayload('owners',
    [[
         'city'     => 'Sacramento',
         'county'   => 34,
         'dl-type'  => 'unlicensed',
         'name:0'   => 'Owner New',
         'state'    => 'CA',
         'street:0' => '4625 Madison Ave',
         'type'     => 'owner',
         'zip'      => 95841,
     ]]);
$api->addPayload('status', 'QF');
$api->addPayload('transaction-type', 5);
$api->addPayload('attributes', 0x04); // no lien holder
$api->send();
$response = json_decode($api->getResult(), true);
while ($retryAttempts++ < $retryMax && $response['deals'][0]['error-code'] == 'CADMV/Q023') {
    error_log('DMV Retry Code Encountered');
    sleep($retryDelayBase * pow(2, $retryAttempts));
    $api->send();
    $response = json_decode($api->getResult(), true);
}
Writer::writeRequestResponse($api);

if (empty($response['deals'][0]['error-code'])) {
    sleep(1); // just to be sure that we don't overwrite the first request/response pair
    $retryAttempts = 0;
    $api->resetPayload();
    $api->setMethod('PUT');
    $api->addPayload('id', $response['deals'][0]['id']);
    $api->addPayload('status', 'QA');
    $api->send();
    $response = json_decode($api->getResult(), true);
    while ($retryAttempts++ < $retryMax && $response['deals'][0]['error-code'] == 'CADMV/Q023') {
        error_log('DMV Retry Code Encountered');
        sleep($retryDelayBase * pow(2, $retryAttempts));
        $api->send();
        $response = json_decode($api->getResult(), true);
    }
    Writer::writeRequestResponse($api);
}