<?php
namespace PaulJulio\AvrsApi;

use \Exception;

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
	private $host;
	private $port = 443;

	const ATTR_CLEAR_RDF            = 0x0001;
	const ATTR_REGCARD_USE_LESSOR   = 0x0002;
	const ATTR_NO_LIENHOLDER        = 0x0004;
	const ATTR_DEALER_ROLLBACK      = 0x0008;
	const ATTR_USE_LOCAL_INVENTORY  = 0x0010;
	const ATTR_USE_LOCATION_FOR_RDF = 0x0020;

	static $rdfCodes = array(
		"3" => "Ownership Certificate or Application for Duplicate",
		"5" => "Verification of Vehicle Identification Number",
		"6" => "Last Registration Card Issued",
		"7" => "Title from the State",
		"8" => "Certificate of Non-Operation",
		"9" => "Referred to California Highway Patrol for Inspection",
		"A" => "Bill of Sale",
		"B" => "Signature",
		"C" => "Motor Vehicle Bond",
		"E" => "Valid Weight Certificate",
		"F" => "Official Brake and Light Adjustment",
		"G" => "Error Statement",
		"H" => "Driver License or Id Card Number",
		"I" => "Lien Satisfied Is Required",
		"J" => "Power of Attorney",
		"K" => "Dealer Report of Sale",
		"L" => "Require Physical Address of Trailer or Vessel",
		"M" => "Letters of Administration or Testamentary",
		"N" => "Statement of Facts",
		"P" => "Transfer Without Probate",
		"Q" => "Certificate of Cost and/or Vehicle Weight Change",
		"R" => "Gross Vehicle Weight Declaration",
		"T" => "Name Statement",
		"U" => "Posting Fees Only",
		"W" => "Odometer Disclosure Statement",
		"Y" => "Lost/Wreck Date",
		"Z" => "Other"
	);

	static $rdfBitmask = array(
		"Z" => 0x00000001,
		"3" => 0x00000002,
		"5" => 0x00000004,
		"6" => 0x00000008,
		"7" => 0x00000010,
		"8" => 0x00000020,
		"9" => 0x00000040,
		"A" => 0x00000080,
		"B" => 0x00000100,
		"C" => 0x00000200,
		"E" => 0x00000400, // 1024, 2^10
		"F" => 0x00000800,
		"G" => 0x00001000,
		"H" => 0x00002000,
		"I" => 0x00004000,
		"J" => 0x00008000,
		"K" => 0x00010000,
		"L" => 0x00020000,
		"M" => 0x00040000,
		"N" => 0x00080000,
		"P" => 0x00100000, // 1048576, 2^20
		"Q" => 0x00200000,
		"R" => 0x00400000,
		"T" => 0x00800000,
		"U" => 0x01000000,
		"W" => 0x02000000,
		"Y" => 0x04000000,
	);


	private function __construct() {}

    /**
     * @param AvrsApiSO $settings
     * @return static
     * @throws Exception
     */
	public static function Factory(AvrsApiSO $settings)
    {
        if (!$settings->isValid()) {
            throw new Exception('Invalid Settings Object');
        }
        $instance = new static;
        $instance->key = $settings->getKey();
        $instance->secret = $settings->getSecret();
        $instance->passphrase = $settings->getPassphrase();
        $instance->host = $settings->getHost();
        $instance->port = $settings->getPort();
        $instance->setSSLVerification($settings->getVerifySSL());
        if ($settings->getDebug()) {
            $instance->enableDebug();
        } else {
            $instance->disableDebug();
        }

        // defaults
        $instance->method = 'GET';
        $instance->url = '/api/v1/authentications/';
        $instance->payload = [];

        return $instance;
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
		$this->payload = [];
	}

	public function getPayload() {
		return $this->payload;
	}

	public function setURL($url) {
		$this->url = $url;
	}

	public function getURL() {
		return $this->url;
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
		$challenge .= 'URI=' . parse_url($this->host . $this->url, PHP_URL_PATH);
		if (parse_url($this->host . $this->url, PHP_URL_QUERY) != '') {
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
		$ch        = curl_init($this->host . $this->url);
		$headers   = array(
			'X-AVRS-Epoch: ' . $this->time,
			'X-AVRS-Key:' . $this->key,
			'X-AVRS-Signature: ' . $signature,
			'X-AVRS-Passphrase: ' . $this->passphrase,
			'Accept: application/json'
		);
		if ($this->debug) {
			$headers[] = 'X-AVRS-Challenge: Display';
			$reqf      = fopen(__DIR__ . '/request.txt', 'w');
			curl_setopt($ch, CURLOPT_STDERR, $reqf);
			curl_setopt($ch, CURLOPT_VERBOSE, true);
		} else {
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_PORT, $this->port);
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
			fclose($reqf);
			$resf = fopen(__DIR__ . '/result.json', 'w');
			fwrite($resf, $this->result);
			fclose($resf);
			$infof = fopen(__DIR__ . '/curlinfo.txt', 'w');
			fwrite($infof, json_encode($this->info));
			fclose($infof);
			$jsonResult = json_decode($this->result, true);
			if ($jsonResult['API-Challenge'] !== $this->challenge) {
				error_log("\n\n");
				error_log(str_repeat("-", 80));
				error_log("\nDEBUG: CHALLENGE MISMATCH!\n");
				error_log("SENT: " . $this->challenge  . "\n");
				error_log("RECV: " . $jsonResult['API-Challenge'] . "\n");
				error_log(str_repeat("-", 80));
				error_log("\n\n");
			}
		}

		return $this->result;
	}

	public function getResult() {
		return $this->result;
	}

    /**
     * @return bool|string False if not in debug mode or the request file is not present, else the contents of the
     * request file.
     */
    public function getRequest() {
		if ($this->debug && file_exists(__DIR__ . '/request.txt')) {
			return file_get_contents(__DIR__ . '/request.txt');
		}
		return false;
	}

	/**
	 * @param mixed $key
	 * @return mixed
	 */
	public function getInfo($key = null) {
		if (isset($key)) {
			if (isset($this->info[$key])) {
				return $this->info[$key];
			}
			return null;
		}
		return $this->info;
	}

	public function getChallenge() {
		return $this->challenge;
	}

	public function enableDebug() {
		if (file_exists(__DIR__ . '/request.txt')) {
			unlink(__DIR__ . '/request.txt');
		}
		if (file_exists(__DIR__ . '/result.json')) {
			unlink(__DIR__ . '/result.json');
		}
		$this->debug = true;
	}

	public function disableDebug() {
		$this->debug = false;
	}
	public function setSSLVerification($bool) {
		$this->verify = $bool;
	}
}
