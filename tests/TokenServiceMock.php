<?php

namespace ostkit\test;

/**
 * Mock class for the '/token' endpoint.
 *
 * @package ostkit\test
 */
class TokenServiceMock extends AbstractServiceMock {
    private $json;

    function __construct() {
        parent::__construct('/token');
        $this->json = json_decode('{
  "success": true,
  "data": {
    "result_type": "token",
    "token": {
      "company_uuid": "ab95e922-26de-44ec-9e5a-9b832c388113",
      "name": "Sample Token",
      "symbol": "SCO",
      "symbol_icon": "token_icon_1",
      "conversion_factor": "14.86660",
      "token_erc20_address": "0x546d41730B98a24F2dCfcdbE15637aD1939Bf38b",
      "simple_stake_contract_address": "0x54eF67a54d8b77C091B6599F1A462Ec7b4dFc648",
      "total_supply": "92701.9999941",
      "ost_utility_balance": [
        [
          "1409",
          "87.982677084999999996"
        ]
      ]
    },
    "price_points": {
      "OST": {
        "USD": "0.177892"
      }
    }
  }
}', true);
    }

    function get($id, $fetchAll, $arguments, $extractResultType) {
        if ($extractResultType) { // token
            return $this->json['data']['token'];
        } else { // price points
            return $this->json;
        }
    }

}