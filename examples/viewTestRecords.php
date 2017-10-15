<?php
namespace PaulJulio\AvrsApi\Examples;

class viewTestRecords extends AbstractExample {

    public function run() {
        $this->api->setURL('/api/v1/test-records/');
        $this->send();
        $this->logApi();
    }
}
