<?php

namespace Ost\Kit\Php\Client\Test;

use Exception;
use Ost\Kit\Php\Client\OstKitClient;

class OstKitMock extends OstKitClient {
    private $users;

    public function createUser($name) {
        parent::createUser($name);
        $uuid = self::uuid();
        $user = json_decode("{
         \"id\": \"$uuid\",
         \"addresses\": [
            [
               \"1409\",
               \"0x9352880A2A4c05c41eC1962980Bb1a0bA4176182\"
            ]
         ],
         \"name\": \"$name\",
         \"airdropped_tokens\": 0,
         \"token_balance\": 0
      }", true);
        $this->users[$uuid] = $user;
        return $user;
    }

    public function updateUser($id, $name) {
        parent::updateUser($id, $name);
        if (isset($this->users[$id])) {
            $user = $this->users[$id];
            $user['name'] = $name;
            return $user;
        }
        throw new Exception('The requested resource could not be located.');
    }

    private static function uuid() {
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
            return '69cc4fcd-39ca-4499-8948-c402dd83fcd8';
        }
    }

    function __construct() {
        parent::__construct('DummyApiKey', 'DummySecret', 'https://sandboxapi.ost.com/v1', true);
        $this->users = array();
    }

    protected function post($endpoint, $arguments = array(), $extractResultType = true) {
        if ($endpoint == '/users') { // create
            $uuid = self::uuid();
            $name = $arguments['name'];
            return json_decode("{
         \"id\": \"$uuid\",
         \"addresses\": [
            [
               \"1409\",
               \"0x9352880A2A4c05c41eC1962980Bb1a0bA4176182\"
            ]
         ],
         \"name\": \"$name\",
         \"airdropped_tokens\": 0,
         \"token_balance\": 0
      }", true);
        } else {
            if (strpos($endpoint, '/users/') == 0) { // update
                $uuid = substr($endpoint, strlen('/users/'));
                $name = $arguments['name'];
                return json_decode("{
         \"id\": \"$uuid\",
         \"addresses\": [
            [
               \"1409\",
               \"0x9352880A2A4c05c41eC1962980Bb1a0bA4176182\"
            ]
         ],
         \"name\": \"$name\",
         \"airdropped_tokens\": 0,
         \"token_balance\": 0
      }", true);
            }
        }
        throw new Exception('POST request failed');
    }

    protected function get($endpoint, $fetchAll, $arguments = array(), $extractResultType = true) {
        if ($endpoint == '/users') { // list
            return $this->users;
        } else {
            if (strpos($endpoint, '/users/') == 0) { // retrieve
                $uuid = substr($endpoint, strlen('/users/'));
                if (isset($this->users[$uuid])) {
                    return $this->users[$uuid];
                } else {
                    throw new Exception('The requested resource could not be located.');
                }
            }
        }
        throw new Exception('GET request failed');
    }

}