<?php
namespace PaulJulio\AvrsApi\Examples;

use PaulJulio\AvrsApi\TestRecords;

class TransferOwnership extends AbstractExample {

    public function run() {

        $bitmask = TestRecords::BIT_AUTO;
        $reservation = $this->getTestRecord($bitmask);

        // create a deal with the required fields
        $this->api->setURL('/api/v1.5/deals/');
        $this->api->setMethod('POST');
        $this->api->addPayload('vehicles',
            [[
                'vin'                   => $reservation['vin'],
                'plate'                 => $reservation['plate'],
                'prior-owner-name'      => $reservation['owner'],
                'prior-lienholder-name' => $reservation['lien-holder'],
                'insurance'             => 'Y', // for testing environment only, certify that the vehicle is insured
                'smog'                  => 'CRT', // for testing environment only, declare Smog Cert In Hand
                'transfer-number'       => 1,
                'use-tax-reclass'       => 'Y',
                'ownership-cert-date'   => '2015-06-06',
                'odometer'              => 123456,
                'odometer-code'         => 'actual',
                'odometer-unit'         => 'M',
                'transfer-date'         => date('Y-m-d'),
                'cost'                  => 5000,
            ]]);
        $this->api->addPayload('owners',
            [[
                'city'     => 'Sacramento',
                'county'   => 34,
                'dl-type'  => 'unlicensed',
                'name:0'   => 'Owner New',
                'state'    => 'CA',
                'street:0' => '4625 Madison Ave',
                'type'     => 'owner',
                'zip'      => 95841,
            ]]);
        $this->api->addPayload('transaction-type', 5);
        $this->api->addPayload('attributes', 0x04); // no lien holder
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
        $this->logApi();

        // pay the fees
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
