<?php
namespace samples;
if (!(class_exists(__NAMESPACE__ . '\Loader'))) {
	require_once(realpath(__DIR__ . '/loader.php'));
}
use api;

class Writer {

	static private $prefixFormat = 'Y-m-d-H-i-s-';

	static public function writeRequest(api\AVRSAPI $api, $prefix = null) {
		if (($request = $api->getRequest()) === false) {
			return;
		}
		if (!isset($prefix)) {
			$prefix = date(self::$prefixFormat);
		}
		$fh = fopen(__DIR__ . '/' . $prefix . 'request.txt', 'w');
		fwrite($fh, $request);
		fclose($fh);
		$fh = fopen(__DIR__ . '/' . $prefix . 'payload.json', 'w');
		fwrite($fh, json_encode($api->getPayload()));
		fclose($fh);
	}

	static public function writeResponse(api\AVRSAPI $api, $prefix = null, $suffix = 'json') {
		if (!isset($prefix)) {
			$prefix = date(self::$prefixFormat);
		}
		$fh = fopen(__DIR__ . '/' . $prefix . 'response.' . $suffix, 'w');
		fwrite($fh, $api->getResult());
		fclose($fh);
	}

	static public function writeRequestResponse(api\AVRSAPI $api, $prefix = null) {
		if (!isset($prefix)) {
			$prefix = date(self::$prefixFormat);
		}
		self::writeRequest($api, $prefix);
		self::writeResponse($api, $prefix);
	}

}
