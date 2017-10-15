<?php
namespace PaulJulio\AvrsApi\Examples;

class FeeCalculatorTwoStep extends AbstractExample {

    public function run() {
        // create a deal with the required fields, request fees after getting a deal with decoded vin info back
        $this->api->setURL('/api/v1.5/deals/');
        $this->api->setMethod('POST');
        $dealData = [
            'owners' => [['zip' => 95492]],
            'vehicles' => [[
                'cost'                => 45000,
                'first-operated-date' => '2017-11-11',
                'first-sold-date'     => '2017-11-11',
                'vin'                 => 'JH4KA8250MC004002',
                'type-license-code'   => 11,
            ]],
            'transaction-type' => 3,
            'gateway-type' => 'CALC-CA',
        ];
        $this->api->addPayload('deals', [$dealData]);
        $this->send();
        $response = $this->api->getResult();
        $this->logApi();
        $this->resetApi();
        $this->api->setURL('/api/v1.5/deals/transactions/');
        $this->api->setMethod('POST');
        $this->api->addPayload('deal-status', 'FR');
        $this->api->addPayload('deal-id', $response['deals'][0]['id']);
        $this->send();
        $this->logApi();
    }
}
