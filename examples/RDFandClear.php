<?php
namespace PaulJulio\AvrsApi\Examples;

use PaulJulio\AvrsApi\AVRSAPI;
use PaulJulio\AvrsApi\Logger;
use PaulJulio\AvrsApi\TestRecords;

class RDFandClear extends AbstractExample {

    public function run() {

        $bitmask = (TestRecords::BIT_COMMERCIAL | TestRecords::BIT_RENEWAL_DUE);
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
        $dealId = $response['deals'][0]['id'];
        $this->logApi();
        // get the fees
        $this->resetApi();
        $this->api->setURL('/api/v1.5/deals/transactions/');
        $this->api->setMethod('POST');
        $this->api->addPayload('deal-status', 'FR');
        $this->api->addPayload('deal-id', $dealId);
        $this->send();

        // pay the fees
        if (empty($response['deals'][0]['error-code'])) {
            sleep(1); // just to be sure that we don't overwrite the first request/response pair
            $this->resetApi();
            $this->api->setURL('/api/v1.5/deals/transactions/');
            $this->api->setMethod('POST');
            $this->api->addPayload('deal-status', 'FP');
            $this->api->addPayload('deal-id', $dealId);
            $this->send();
            $response = json_decode($this->api->getResult(), true);
            $this->logApi();
        }
        // download the RDF Receipt
        if (empty($response['deals'][0]['error-code'])) {
            sleep(1); // just to be sure that we don't overwrite the first request/response pair
            $this->api->resetPayload();
            $this->api->setMethod('GET');
            $this->api->setURL('/api/v1/deals/?pdf=1&id=' . $dealId);
            $this->send();
            $response = json_decode($this->api->getResult(), true);
            Logger::writeRequest($this->api);
            if ($this->api->getInfo('http_code') == 200) {
                Logger::writeResponse($this->api, null, 'pdf');
            } else {
                Logger::writeResponse($this->api, null, 'txt');
            }
        }
        // transition into a ready state
        if (empty($response['deals'][0]['error-code'])) {
            sleep(1); // just to be sure that we don't overwrite the first request/response pair
            $this->resetApi();
            $this->api->setURL('/api/v1.5/deals/');
            $this->api->setMethod('PUT');
            $this->api->addPayload('id', $response['deals'][0]['id']);
            $this->api->addPayload('status', 'R');
            $this->api->addPayload('attributes', AVRSAPI::ATTR_CLEAR_RDF);
            $this->send();
            $response = json_decode($this->api->getResult(), true);
            $this->logApi();
        }

        // transition into a clearing-fees state
        if (empty($response['deals'][0]['error-code'])) {
            sleep(1); // just to be sure that we don't overwrite the first request/response pair
            $this->resetApi();
            $this->api->setURL('/api/v1.5/deals/transactions/');
            $this->api->setMethod('POST');
            $this->api->addPayload('deal-status', 'FR');
            $this->api->addPayload('deal-id', $dealId);
            $this->send();
            $response = json_decode($this->api->getResult(), true);
            $this->logApi();
        }

        // accept the clearing-fees state
        if (empty($response['deals'][0]['error-code'])) {
            sleep(1); // just to be sure that we don't overwrite the first request/response pair
            $this->resetApi();
            $this->api->setURL('/api/v1.5/deals/transactions/');
            $this->api->setMethod('POST');
            $this->api->addPayload('deal-status', 'C');
            $this->api->addPayload('deal-id', $dealId);
            $this->send();
            $this->logApi();
        }
    }
}

