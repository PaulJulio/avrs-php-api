<?php
namespace samples;
if (!(class_exists(__NAMESPACE__ . '\Loader'))) {
    require_once(realpath(__DIR__ . '/loader.php'));
}
use api\AVRSAPI as AVRSAPI;
use api\TestRecords as TestRecords;

$bitmask = (TestRecords::BIT_COMMERCIAL | TestRecords::BIT_RENEWAL_DUE);
// exponential backoff settings
$retryAttempts  = 0;
$retryMax       = 3;
$retryDelayBase = 3;

// do we have an inventory reservation already?
// check from newest reservation to oldest

$haveReservation = false;
$reservations = array_reverse(TestRecords::getRecords());
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
// indicate that this is a posting-fees transaction
$api = new AVRSAPI();
$api->setURL('/api/v1/deals/');
$api->setMethod('POST');
$api->addPayload('vehicles', array(array(
   'vin'       => $reservation['vin']   ,
   'plate'     => $reservation['plate'] ,
   'insurance' => 'Y'                   , // for testing environment only, certify that the vehicle is insured
   'smog'      => 'CRT'                 , // for testing environment only, declare Smog Cert In Hand
)));
$api->addPayload('status', 'QF');
$api->addPayload('transaction-type', 6);
$api->addPayload('rdf', AVRSAPI::$rdfBitmask['U']); // RDF Code U: Posting Fees Only
$api->addPayload('avs', array(json_encode(array(
    'street:0' => '770 E SHAW',
    'zip'      => 93710
)))); // the most common address for commercial vehicles is this Pac Bell address
$api->send();
$response = json_decode($api->getResult(), true);
while ($retryAttempts++ < $retryMax && $response['deals'][0]['error-code'] == 'CADMV/Q023') {
    error_log('DMV Retry Code Encountered');
    sleep($retryDelayBase * pow(2, $retryAttempts));
    $api->send();
    $response = json_decode($api->getResult(), true);
}
Writer::writeRequestResponse($api);

// pay the fees
if (empty($response['deals'][0]['error-code'])) {
    sleep(1); // just to be sure that we don't overwrite the first request/response pair
    $retryAttempts = 0;
    $api->resetPayload();
    $api->setMethod('PUT');
    $api->setURL('/api/v1/deals/');
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
// download the RDF Receipt
if (empty($response['deals'][0]['error-code'])) {
    sleep(1); // just to be sure that we don't overwrite the first request/response pair
    $retryAttempts = 0;
    $api->resetPayload();
    $api->setMethod('GET');
    $api->setURL('/api/v1/deals/?pdf=1&id=' . $response['deals'][0]['id']);
    $api->send();
    $response = json_decode($api->getResult(), true);
    while ($retryAttempts++ < $retryMax && $response['deals'][0]['error-code'] == 'CADMV/Q023') {
        error_log('DMV Retry Code Encountered');
        sleep($retryDelayBase * pow(2, $retryAttempts));
        $api->send();
        $response = json_decode($api->getResult(), true);
    }
    Writer::writeRequest($api);
    if ($api->getInfo('http_code') == 200) {
        Writer::writeResponse($api, null, 'pdf');
    } else {
        Writer::writeResponse($api, null, 'txt');
    }
}

// transition into a ready state
if (empty($response['deals'][0]['error-code'])) {
    sleep(1); // just to be sure that we don't overwrite the first request/response pair
    $retryAttempts = 0;
    $api->resetPayload();
    $api->setMethod('PUT');
    $api->addPayload('id', $response['deals'][0]['id']);
    $api->addPayload('status', 'R');
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

// transition into a clearing-fees state
if (empty($response['deals'][0]['error-code'])) {
    sleep(1); // just to be sure that we don't overwrite the first request/response pair
    $retryAttempts = 0;
    $api->resetPayload();
    $api->setMethod('PUT');
    $api->addPayload('id', $response['deals'][0]['id']);
    $api->addPayload('status', 'QF');
    $api->addPayload('attributes', AVRSAPI::ATTR_CLEAR_RDF);
    $api->addPayload('rdf', 0);
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

// accept the clearing-fees state
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
