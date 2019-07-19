<?php
namespace PaulJulio\AvrsApi;

final class TestRecords {

	const BIT_AUTO               = 0x0000001; // an auto, like a car
	const BIT_COMMERCIAL         = 0x0000002; // pickup or other commercial vehicle
	const BIT_REVERSE_COMMERCIAL = 0x0000004; // picker or other commercial vehicle with a reversed plate sequence
	const BIT_MOTORCYCLE         = 0X0000008; // motorcycle
	const BIT_TRAILER            = 0x0000010; // trailer
	const BIT_VESSEL             = 0x0000020; // boat
	const BIT_ELP                = 0x0000040; // personalized plate
	const BIT_EXPIRED            = 0x0000080; // expired reg
	const BIT_HAS_LIEN_HOLDER    = 0x0000100; // vehicle has a lien holder
	const BIT_PNO_ON_FILE        = 0x0000200; // vehicle is in Planned-Non-Operation status
	const BIT_SALVAGE            = 0X0000400; // vehicle is salvaged
	const BIT_LSR_LSE            = 0x0000800; // vehicle is leased
	const BIT_CGW_15000          = 0x0001000; // Combined Gross Weight of 15000 or less
	const BIT_CGW_20000          = 0x0002000;
	const BIT_CGW_25000          = 0x0004000;
	const BIT_CGW_30000          = 0x0008000;
	const BIT_CGW_35000          = 0x0010000;
	const BIT_CGW_40000          = 0x0020000;
	const BIT_CGW_45000          = 0x0040000;
	const BIT_CGW_50000          = 0x0080000;
	const BIT_CGW_54999          = 0x0100000;
	const BIT_CGW_60000          = 0x0200000;
	const BIT_CGW_65000          = 0x0400000;
	const BIT_CGW_70000          = 0x0800000;
	const BIT_CGW_75000          = 0x1000000;
	const BIT_CGW_80000          = 0x2000000;
	const BIT_RDF_ON_FILE        = 0x4000000; // a Report of Deposit of Fees is on file
	const BIT_RENEWAL_DUE        = 0x8000000; // vehicle is due for renewal

	public static function getRecords(AvrsApiSO $settings) {
		$api = AVRSAPI::Factory($settings);
		$api->setURL('/api/v1/test-records/');
		$api->send();
		$result = json_decode($api->getResult(), true);
		if (isset($result['test-records'])) {
			return $result['test-records'];
		}
		return [];
	}

	public static function reserveRecord(AvrsApiSO $settings, $conditions = 0xfffffff) {
        $api = AVRSAPI::Factory($settings);
		$api->setURL('/api/v1/test-records/');
		$api->setMethod('POST');
		$api->addPayload('conditions', $conditions);
		$api->send();
		$result = json_decode($api->getResult(), true);
		if (isset($result['test-records'])) {
			return $result['test-records'][0];
		}
		return array();
	}
}
