<?php

namespace ostkit\test;

use PHPUnit\Framework\TestCase;
use ostkit\OstKitClient;

/**
 * Test case for mocked interactions with the '/actions' endpoint.
 */
class TokenTest extends TestCase {
    private $ost;

    public function __construct($name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);
        $this->ost = new OstKitMock();
    }

    /**
     * @covers OstKitClient::getToken
     */
    public function testGetToken() {
        $token = $this->ost->getToken();
        self::assertNotNull($token, 'Retrieved token should not be null.');
        self::assertEquals('Sample Token', $token['name']);
    }

    /**
     * @covers OstKitClient::getToken
     */
    public function testGetOstPricePoints() {
        $pricePoints = $this->ost->getOstPricePoints();
        self::assertNotNull($pricePoints, 'OST price points should not be null.');
        if (!isset($pricePoints['USD'])) {
            self::fail("At least the price point for OST/USD should at be available");
        } else {
            self::assertTrue(is_numeric($pricePoints['USD']), 'Price point for OST/USD should be a numeric value');
        }
    }
}