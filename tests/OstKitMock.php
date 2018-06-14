<?php

namespace ostkit\test;

use Exception;
use ostkit\OstKitClient;

/**
 * Class OstKitMock that mocks the POST/GET calls of the OstKitClient for unit testing purposes.
 *
 * @package ostkit\test
 * @author Jay Nay
 * @version 1.0
 */
class OstKitMock extends OstKitClient {
    private $services;
    private $users;

    function __construct() {
        parent::__construct('DummyApiKey', 'DummySecret', 'https://sandboxapi.ost.com/v1', true);
        $this->users = array();
        $this->services = array(new UserServiceMock(), new AirdropServiceMock(), new ActionServiceMock(), new TransactionServiceMock(), new TransferServiceMock(), new TokenServiceMock());
    }

    protected function post($endpoint, $arguments = array(), $extractResultType = true) {
        foreach ($this->services as $service) {
            if ($service->accepts($endpoint)) {
                return $service->post(self::extractId($endpoint), $arguments);
            }
        }
        throw new Exception("POST request failed - unknown endpoint: $endpoint");
    }

    protected function get($endpoint, $fetchAll, $arguments = array(), $extractResultType = true) {
        foreach ($this->services as $service) {
            if ($service->accepts($endpoint)) {
                return $service->get(self::extractId($endpoint), $arguments, $fetchAll);
            }
        }
        throw new Exception("GET request failed - unknown endpoint: $endpoint");
    }

    static function extractId($endpoint) {
        if (isset($endpoint)) {
            preg_match('/\/\w+(\/(' . self::UUID_REGEX . ')?)?/', $endpoint, $matches);
            if (isset($matches[2])) {
                return $matches[2];
            }
            if (isset($matches[1])) {
                return '';
            }
        }
        return null;
    }

    static function address() {
        try {
            if (function_exists('random_bytes')) {
                $data = random_bytes(20);
                return vsprintf('0x%s', str_split(bin2hex($data), 40));
            } else {
                $data = array_fill(0, 20, mt_rand(0, 0xffff));
                return vsprintf('0x%04x%04x%04x%04x%04x%04x%04x%04x%04x%04x', $data);
            }
        } catch (Exception $ignored) {
            print $ignored->getMessage();
        }
        return FALSE;
    }

    static function uuid() {
        try {
            if (function_exists('random_bytes')) {
                $data = random_bytes(16);
                $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
                $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
                return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
            } else {
                return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    // 32 bits for "time_low"
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),

                    // 16 bits for "time_mid"
                    mt_rand(0, 0xffff),

                    // 16 bits for "time_hi_and_version",
                    // four most significant bits holds version number 4
                    mt_rand(0, 0x0fff) | 0x4000,

                    // 16 bits, 8 bits for "clk_seq_hi_res",
                    // 8 bits for "clk_seq_low",
                    // two most significant bits holds zero and one for variant DCE1.1
                    mt_rand(0, 0x3fff) | 0x8000,

                    // 48 bits for "node"
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
            }
        } catch (Exception $ignored) {
            print $ignored->getMessage();
        }
        return FALSE;
    }

}

abstract class ServiceMock {
    private $endpoint;

    protected function __construct($endpoint) {
        $this->endpoint = $endpoint;
    }

    function accepts($endpoint) {
        return isset($endpoint) && strpos($endpoint, '/users') == 0;
    }
}

class UserServiceMock extends ServiceMock {
    private $users = array();

    function __construct() {
        parent::__construct('/users');
    }

    function get($id, $arguments = array()) {
        if (isset($id)) { // retrieve
            if (isset($this->users[$id])) {
                return $this->users[$id];
            }
            throw new Exception('The requested resource could not be located.');
        } else { // list
            return $this->users;
        }
        throw new Exception('GET request failed');
    }

    function post($id, $arguments) {
        if (isset($id)) { // update
            if (isset($this->users[$id])) {
                if (isset($this->users[$id])) {
                    $user = $this->users[$id];
                    $user['name'] = $arguments['name'];
                    return $user;
                }
                throw new Exception('The requested resource could not be located.');
            }
        } else { // create
            $uuid = OstKitMock::uuid();
            $address = OstKitMock::address();
            $name = $arguments['name'];
            $user = json_decode("{\"id\": \"$uuid\", \"addresses\": [[ \"1409\", \"$address\"]], \"name\": \"$name\", \"airdropped_tokens\": 0, \"token_balance\": 0}", true);
            $this->users[$uuid] = $user;
            return $user;
        }
        throw new Exception('POST request failed');
    }
}

class ActionServiceMock extends ServiceMock {
    function __construct() {
        parent::__construct('/actions');
    }
}

class AirdropServiceMock extends ServiceMock {
    function __construct() {
        parent::__construct('/actions');
    }
}

class TransactionServiceMock extends ServiceMock {
    function __construct() {
        parent::__construct('/transactions');
    }
}

class TransferServiceMock extends ServiceMock {
    function __construct() {
        parent::__construct('/transfers');
    }
}

class TokenServiceMock extends ServiceMock {
    function __construct() {
        parent::__construct('/token');
    }

    function get() {
        return '{
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
}';
    }
}