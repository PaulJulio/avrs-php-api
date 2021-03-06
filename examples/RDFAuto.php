<?php
namespace PaulJulio\AvrsApi\Examples;

class RDFAuto extends AbstractExample {

    public function run() {

        $bitmask = (TestRecords::BIT_AUTO | TestRecords::BIT_RENEWAL_DUE);
        $reservation = $this->getTestRecord($bitmask);

        // create a deal with the required fields and immediately request fees
        // indicate that this is a posting-fees transaction
        $this->api->setURL('/api/v1.5/deals/');
        $this->api->setMethod('POST');
        $this->api->addPayload('vehicles', array(array(
            'vin'       => $reservation['vin']   ,
            'plate'     => $reservation['plate'] ,
            'insurance' => 'Y'                   , // for testing environment only, certify that the vehicle is insured
            'smog'      => 'CRT'                 , // for testing environment only, declare Smog Cert In Hand
        )));
        $this->api->addPayload('transaction-type', 6);
        $this->api->addPayload('rdf', AVRSAPI::$rdfBitmask['U']); // RDF Code U: Posting Fees Only
        $this->send();
        $response = json_decode($this->api->getResult(), true);
        $dealId = $response['deals'][0]['id'];;
        $this->logApi();
        // get the fees
        $this->resetApi();
        $this->api->setURL('/api/v1.5/deals/transactions/');
        $this->api->setMethod('POST');
        $this->api->addPayload('deal-status', 'FR');
        $this->api->addPayload('deal-id', $dealId);
        $this->send();
        $this->logApi();

        // pay the fees
        if (empty($response['deals'][0]['error-code'])) {
            sleep(1); // just to be sure that we don't overwrite the first request/response pair
            $this->resetApi();
            $this->api->setURL('/api/v1.5/deals/transactions/');
            $this->api->setMethod('POST');
            $this->api->addPayload('deal-status', 'FP');
            $this->api->addPayload('deal-id', $dealId);
            $this->send();
            $this->logApi();
        }
    }
}

