<?php

namespace PaulJulio\AvrsApi;

class AvrsApiSO {

    private $key;
    private $secret;
    private $passphrase;
    private $verifySSL;
    private $debug;
    private $host;
    private $port;

    public function __construct() {}

    public function isValid()
    {
        return (
            isset($this->key)
            && isset($this->secret)
            && isset($this->passphrase)
            && isset($this->verifySSL)
            && isset($this->debug)
            && isset($this->host)
            && isset($this->port)
        );
    }

    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key) {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getSecret() {
        return $this->secret;
    }

    /**
     * @param string $secret
     */
    public function setSecret($secret) {
        $this->secret = $secret;
    }

    /**
     * @return string
     */
    public function getPassphrase() {
        return $this->passphrase;
    }

    /**
     * @param string $passphrase
     */
    public function setPassphrase($passphrase) {
        $this->passphrase = $passphrase;
    }

    /**
     * @return bool
     */
    public function getVerifySSL() {
        return $this->verifySSL;
    }

    /**
     * @param bool $verifySSL
     */
    public function setVerifySSL($verifySSL) {
        $this->verifySSL = $verifySSL;
    }

    /**
     * @return bool
     */
    public function getDebug() {
        return $this->debug;
    }

    /**
     * @param bool $debug
     */
    public function setDebug($debug) {
        $this->debug = $debug;
    }

    /**
     * @return string
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost($host) {
        $this->host = $host;
    }

    /**
     * @return int
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort($port) {
        $this->port = $port;
    }

}