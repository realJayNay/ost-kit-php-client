<?php

namespace ostkit\test;

use Exception;

class UserServiceMock extends AbstractServiceMock {
    private $users = array();

    function __construct() {
        parent::__construct('/users');
    }

    function get($id, $arguments, $fetchAll, $extractResultTYpe) {
        if (isset($id)) { // retrieve
            if (isset($this->users[$id])) {
                return $this->users[$id];
            }
            throw new Exception('The requested resource could not be located.');
        } else { // list
            return $this->users;
        }
    }

    function post($id, $arguments, $extractResultType) {
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