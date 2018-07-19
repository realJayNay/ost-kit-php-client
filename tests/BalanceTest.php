<?php

namespace ostkit\test;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BalanceTest extends TestCase {
    private $ost;

    public function __construct($name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);
        $this->ost = new OstKitMock();
    }

    /**
     * @covers OstKitClient::getBalance
     */
    public function testGetBalance() {
        $id = 'f5f9b061-b784-4ecd-b599-bc263860f539';
        $balance = $this->ost->getBalance($id);
        self::assertNotNull($balance, 'Valid balance should not be null.');
    }

    /**
     * @covers OstKitClient::getBalance
     * @expectedException Exception
     * @expectedExceptionMessage Resource matching the id could not be located.
     */
    public function testGetBalanceUnknownUserId() {
        $id = 'b4f9b061-b784-4ecd-b599-bc263860f539';
        $balance = $this->ost->getBalance($id);
        self::assertNotNull($balance, 'Valid balance should not be null.');
    }

    /**
     * @covers OstKitClient::getBalance
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage  is not a valid UUID.
     */
    public function testGetBalanceInvalidUserId() {
        $id = '12f9b061-b784-4ecd-b599-bc263860f53_';
        $balance = $this->ost->getBalance($id);
        self::assertNotNull($balance, 'Valid balance should not be null.');
    }

    /**
     * @covers OstKitClient::getBalance
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage ID is mandatory.
     */
    public function testGetBalanceNullUserId() {
        $balance = $this->ost->getBalance(null);
        self::assertNotNull($balance, 'Valid balance should not be null.');
    }

    /**
     * @covers OstKitClient::getCombinedBalance
     */
    public function testGetCombinedBalance() {
        $balance = $this->ost->getCombinedBalance('f5f9b061-b784-4ecd-b599-bc263860f539');
        self::assertNotNull($balance, 'Valid combined balance array should not be null.');
        self::assertTrue(isset($balance['ost_value']), 'Combined balance must contain OST countervalue');
        self::assertTrue(isset($balance['usd_value']), 'Combined balance must contain USD countervalue');
    }
}