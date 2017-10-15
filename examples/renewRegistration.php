<?php
namespace PaulJulio\AvrsApi\Examples;

use PaulJulio\AvrsApi\TestRecords;

class renewRegistration extends AbstractExample {

    public function run() {

        $bitmask = (TestRecords::BIT_MOTORCYCLE | TestRecords::BIT_RENEWAL_DUE);
        $reservation = $this->getTestRecord($bitmask);

        // create a deal with the required fields and save
        $this->api = new AVRSAPI();
        $this->api->setURL('/api/v1.5/deals/');
        $this->api->setMethod('POST');
        $this->api->addPayload('vehicles', [[
           'vin'       => $reservation['vin']   ,
           'plate'     => $reservation['plate'] ,
           'insurance' => 'Y'                   , // for testing environment only, certify that the vehicle is insured
       ]]);
        $this->api->addPayload('transaction-type', 6);
        $this->send();
        $response = json_decode($this->api->getResult(), true);
        $this->logApi();
        sleep(1);
        // create a deal transaction for our deal, providing the desired end state (FR, FP or C)
        $this->resetApi();
        $this->api->setURL('/api/v1.5/deals/transactions/');
        $this->api->setMethod('POST');
        $this->api->addPayload('deal-id', $response['deals'][0]['id']);
        $this->api->addPayload('status', 'FR'); // getting fees, which should be checked for sanity
        $this->send();
        $response = json_decode($this->api->getResult(), true);
        $this->logApi();

        if (empty($response['error'])) {
            sleep(1); // just to be sure that we don't overwrite the first request/response pair
            $this->resetApi();
            $this->api->addPayload('deal-id', $response['deals'][0]['id']);
            $this->api->addPayload('deal-status', 'C'); // accepting fees
            $this->send();
            $this->logApi();
        }
    }
}

