<?php

namespace ostkit\test;

use Exception;
use ostkit\OstKitClient;
use ReflectionMethod;

/**
 * Class OstKitMock that mocks the POST/GET calls of the OstKitClient for unit testing purposes.
 *
 * @package ostkit\test
 * @author Jay Nay
 * @version 1.0
 */
class OstKitMock extends OstKitClient {
    private $services;

    function __construct() {
        parent::__construct('DummyApiKey', 'DummySecret', 'https://localhost/v1', true);
        $this->services = self::getServices();
    }

    protected function getServices() {
        return array(new UserServiceMock(), new TokenServiceMock());
    }

    protected function get($endpoint, $fetchAll, $arguments = array(), $extractResultType = true) {
        foreach ($this->services as $service) {
            if ($service->accepts($endpoint)) {
                return $service->get(self::extractId($endpoint), $fetchAll, $arguments, $extractResultType);
            }
        }
        throw new Exception("GET request failed - unknown endpoint: $endpoint");
    }

    protected function post($endpoint, $arguments = array(), $extractResultType = true) {
        foreach ($this->services as $service) {
            if ($service->accepts($endpoint)) {
                $reflector = new ReflectionMethod($service, 'post'); // POST is optional for endpoints
                if ($reflector->getDeclaringClass()->getName() !== get_parent_class($service)) {
                    return $service->post(self::extractId($endpoint), $arguments, $extractResultType);
                } else {
                    throw new Exception("POST request failed - method not supported by endpoint: $endpoint");
                }
            }
        }
        throw new Exception("POST request failed - unknown endpoint: $endpoint");
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