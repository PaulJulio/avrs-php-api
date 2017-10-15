<?php
namespace PaulJulio\AvrsApi\Examples;

class Tokens extends AbstractExample {

    /*
     * An example of how to create/delete authentication keys
     */
    public function run() {

        $this->api->setURL('/api/v1.5/apiauthkeys/');
        $this->api->setMethod('POST');
        $this->api->addPayload('passphrase', md5('this is my unhashed passphrase'));
        $this->send();
        $this->logApi();
        $response = json_decode($this->api->getResult(),true);
        $this->resetApi();
        $this->api->setURL('/api/v1.5/apiauthkeys/');
        $this->api->setMethod('DELETE');
        $this->api->addPayload('id', $response['keys'][0]['id']);
        $this->send();
        $this->logApi();
    }
}

