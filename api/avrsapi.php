<?php
namespace api;
if (!(class_exists(__NAMESPACE__ . '\Loader'))) {
	require_once(realpath(__DIR__ . '/loader.php'));
}

use util\Settings as Settings;

final class AVRSAPI {

	private $key;
	private $secret;
	private $passphrase;
	private $method;
	private $payload;
	private $url;
	private $time;
	private $challenge;
	private $result;
	private $info;
	private $debug  = false;
	private $verify = true;
	private $environment = 'sandbox'; // sandbox | secure

	public function __construct() {
		$this->key        = Settings::get($this->environment.'/key');
		$this->secret     = Settings::get($this->environment.'/secret');
		$this->passphrase = Settings::get($this->environment.'/passphrase');
		$this->method     = 'GET';
		$this->url        = '/api/v1/authentications/';
		$this->payload    = array();
	}

	public function setMethod($method) {
		switch ($method) {
			case 'POST':
			case 'PUT':
			case 'GET':
			case 'DELETE':
			$this->method = $method;
				break;
			default:
			throw new Exception('Unrecognized Method');
		}
	}

	public function addPayload($key, $value = '') {
		if (is_array($key)) {
			$this->payload = array_merge($this->payload, $key);
		} else {
			$this->payload[$key] = $value;
		}
	}

	public function removePayload($key) {
		unset($this->payload[$key]);
	}

	public function resetPayload() {
		$this->payload = array();
	}

	public function setURL($url) {
		$this->url = $url;
	}

	public function getURL() {
		return Settings::get($this->environment . '/domain') . $this->url;
	}

	private function composeChallenge() {
		$this->time = time();
		$challenge  = '';
		if ($this->debug) {
			$challenge .= 'X-AVRS-Challenge=Display;';
		}
		$challenge .= 'X-AVRS-Epoch=' . $this->time . ';';
		$challenge .= 'X-AVRS-Key=' . $this->key . ';';
		$challenge .= 'X-AVRS-Passphrase=' . $this->passphrase . ';';
		$challenge .= 'Method=' . $this->method . ';';
		$challenge .= self::collapseKeyValues($this->payload);
		$challenge .= 'URI=' . parse_url($this->getURL(), PHP_URL_PATH);
		if (parse_url($this->getURL(), PHP_URL_QUERY) != '') {
			$challenge .= '?' . parse_url($this->url, PHP_URL_QUERY);
		}
		$challenge .= ';';
		$this->challenge = $challenge;
	}

	private static function collapseKeyValues(array &$pairs) {
		ksort($pairs);
		$result = "";
		foreach($pairs as $key => &$value) {
			$result .= $key .'='. (is_array($value) ? self::collapseKeyValues($value) : $value) . ';' ;
		}
		return $result;
	}
	
	public function send() {
		$this->composeChallenge();
		$signature = md5($this->challenge . $this->secret . md5($this->passphrase));
		$ch        = curl_init($this->getURL());
		$headers   = array(
			'X-AVRS-Epoch: ' . $this->time,
			'X-AVRS-Key:' . $this->key,
			'X-AVRS-Signature: ' . $signature,
			'X-AVRS-Passphrase: ' . $this->passphrase,
			'Accept: application/json'
		);
		if ($this->debug) {
			$headers[] = 'X-AVRS-Challenge: Display';
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		if ($this->method == 'GET') {
			curl_setopt($ch, CURLOPT_HTTPGET, true);
		} elseif ($this->method == 'POST') {
			curl_setopt($ch, CURLOPT_POST, true);
		} else {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
		}
		if (count($this->payload) > 0) {
			// work-around to include null values in payload as empty strings (http_build_query removes them otherwise)
			$realPayload = $this->payload;
			array_walk_recursive($realPayload, function(&$value) {
					if (!isset($value)) {
						$value = "";
					}
			});
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($realPayload));
		}
		if ($this->verify === false) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}
		$this->result = curl_exec($ch);
		$this->info   = curl_getinfo($ch);
		curl_close($ch);

		if ($this->debug) {
			$jsonResult = json_decode($this->result, true);
			if ($jsonResult['API-Challenge'] !== $this->challenge) {
				echo "\n\n";
				echo str_repeat("-", 80);
				echo "\nDEBUG: CHALLENGE MISMATCH!\n";
				echo "SENT: " . $this->challenge  . "\n";
				echo "RECV: " . $jsonResult['API-Challenge'] . "\n";
				echo str_repeat("-", 80);
				echo "\n\n";
			}
		}

		return $this->result;
	}

	public function getResult() {
		return $this->result;
	}

	public function getInfo() {
		return $this->info;
	}

	public function getChallenge() {
		return $this->challenge;
	}

	public function enableDebug() {
		$this->debug = true;
	}

	public function disableDebug() {
		$this->debug = false;
	}
	public function setSSLVerification($bool) {
		$this->verify = $bool;
	}
}
