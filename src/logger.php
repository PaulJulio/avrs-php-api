<?php
namespace PaulJulio\AvrsApi;

class Logger {

	static private $prefixFormat = 'Y-m-d-H-i-s-';

	static public function writeRequest(AVRSAPI $api, $prefix = null, $dir = null) {
		if (($request = $api->getRequest()) === false) {
			return;
		}
		if (!isset($prefix)) {
			$prefix = date(self::$prefixFormat);
		}
		if (!isset($dir)) {
		    $dir = __DIR__ . '/../log/';
        }
		$fh = fopen($dir . $prefix . 'request.txt', 'w');
		fwrite($fh, $request);
		fclose($fh);
		$fh = fopen($dir . $prefix . 'payload.json', 'w');
		fwrite($fh, json_encode($api->getPayload()));
		fclose($fh);
	}

	static public function writeResponse(AVRSAPI $api, $prefix = null, $suffix = 'json', $dir = null) {
		if (!isset($prefix)) {
			$prefix = date(self::$prefixFormat);
		}
        if (!isset($dir)) {
            $dir = __DIR__ . '/../log/';
        }
		$fh = fopen($dir . $prefix . 'response.' . $suffix, 'w');
		fwrite($fh, $api->getResult());
		fclose($fh);
	}

	static public function writeRequestResponse(AVRSAPI $api, $prefix = null, $suffix = null, $dir = null) {
		if (!isset($prefix)) {
			$prefix = date(self::$prefixFormat);
		}
        if (!isset($dir)) {
            $dir = __DIR__ . '/../log/';
        }
        if (!isset($suffix)) {
		    $suffix = 'json';
        }
		self::writeRequest($api, $prefix, $dir);
		self::writeResponse($api, $prefix, $suffix, $dir);
	}

}
