<?php
namespace samples;
if (!(class_exists(__NAMESPACE__ . '\Loader'))) {
	require_once(realpath(__DIR__ . '/loader.php'));
}
use api\AVRSAPI as AVRSAPI;
use api\TestRecords as TestRecords;

$bitmask = (TestRecords::BIT_MOTORCYCLE | TestRecords::BIT_RENEWAL_DUE);
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

// create a deal with the required fields and save
$api = new AVRSAPI();
$api->setURL('/api/v1/deals/');
$api->setMethod('POST');
$api->addPayload('vehicles', array(array(
	'vin'       => $reservation['vin']   ,
	'plate'     => $reservation['plate'] ,
	'insurance' => 'Y'                   , // for testing environment only, certify that the vehicle is insured
)));
$api->addPayload('transaction-type', 6);
$api->send();
$response = json_decode($api->getResult(), true);
Writer::writeRequestResponse($api);
sleep(1);
// create a deal transaction for our deal, providing the desired end state (FR, FP or C)
$api = new AVRSAPI();
$api->setURL('/api/v1.5/deals/transactions/');
$api->setMethod('POST');
$api->addPayload('deal-id', $response['deals'][0]['id']);
$api->addPayload('status', 'FR'); // getting fees, which should be checked for sanity
while ($retryAttempts++ < $retryMax && $response['error']['code'] == 'CADMV/Q023') {
	error_log('DMV Retry Code Encountered');
	sleep($retryDelayBase * pow(2, $retryAttempts));
	$api->send();
	$response = json_decode($api->getResult(), true);
    Writer::writeRequestResponse($api);
}

if (empty($response['error'])) {
	sleep(1); // just to be sure that we don't overwrite the first request/response pair
	$retryAttempts = 0;
	$api->resetPayload();
	$api->addPayload('deal-id', $response['deals'][0]['id']);
	$api->addPayload('deal-status', 'C'); // accepting fees
	$api->send();
	$response = json_decode($api->getResult(), true);
	while ($retryAttempts++ < $retryMax && $response['error']['code'] == 'CADMV/Q023') {
		error_log('DMV Retry Code Encountered');
		sleep($retryDelayBase * pow(2, $retryAttempts));
		$api->send();
		$response = json_decode($api->getResult(), true);
	}
	Writer::writeRequestResponse($api);
}
