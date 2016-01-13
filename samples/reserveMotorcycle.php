<?php
namespace samples;
if (!(class_exists(__NAMESPACE__ . '\Loader'))) {
    require_once(realpath(__DIR__ . '/loader.php'));
}
use api\AVRSAPI as AVRSAPI;
use api\TestRecords as TestRecords;

$bitmask     = (TestRecords::BIT_MOTORCYCLE | TestRecords::BIT_RENEWAL_DUE);
$reservation = TestRecords::reserveRecord($bitmask);

error_log(sprintf('Reserved Plate: %s VIN: %s', $reservation['plate'], $reservation['vin']), E_NOTICE);
