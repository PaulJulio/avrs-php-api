<?php
namespace PaulJulio\AvrsApi\Examples;

use PaulJulio\AvrsApi\Logger;

class PDF extends AbstractExample {

    public function run() {
        $json   = json_encode(array(
            '_gte' => '-2 hours',
            '_lte' => 'now'
        ));
        $url = '/api/v1/deals/?pdf=1&accept-time=' . urlencode($json);

        $this->api->setURL($url);
        $this->send();
        Logger::writeRequest($this->api);
        if ($this->api->getInfo('http_code') == 200) {
            Logger::writeResponse($this->api, null, 'pdf');
        } else {
            Logger::writeResponse($this->api, null, 'txt');
        }
    }
}

