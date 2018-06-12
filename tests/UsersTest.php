<?php

use Ost\Kit\Php\Client\Test\OstKitMock;
use PHPUnit\Framework\TestCase;

/**
 * Test case for interaction with the '/users' endpoint.
 */
class UsersTest extends TestCase {
    private $ost;

    public function __construct($name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);
        $this->ost = new OstKitMock();
    }

    public function testCreateUser() {
        $name = 'Freddy';
        $user = $this->ost->createUser($name);
        self::assertNotNull($user, 'Valid user should not be null.');
        self::assertEquals($name, $user['name']);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Name is mandatory.
     */
    public function testCreateUserNull() {
        $this->ost->createUser(null);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Name must be a minimum of 3 characters, a maximum of 20 characters, and can contain only letters, numbers, and spaces, along with other common sense limitations.
     */
    public function testCreateUserTooShort() {
        $this->ost->createUser('01');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Name must be a minimum of 3 characters, a maximum of 20 characters, and can contain only letters, numbers, and spaces, along with other common sense limitations.
     */
    public function testCreateUserTooLong() {
        $this->ost->createUser('012345678901234567890');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Name must be a minimum of 3 characters, a maximum of 20 characters, and can contain only letters, numbers, and spaces, along with other common sense limitations.
     */
    public function testCreateUserIllegalCharacter() {
        $this->ost->createUser('012345678901234567_/');
    }

    public function testUpdateUser() {
        $olga = $this->ost->createUser('Olga');
        self::assertNotNull($olga, 'Internal disturbance in the force? - valid olga should not be null.');
        $name = 'Helga';
        $helga = $this->ost->updateUser($olga['id'], $name);
        self::assertNotNull($helga, 'Valid user should not be null.');
        self::assertEquals($olga['id'], $helga['id']);
        self::assertEquals($name, $helga['name']);
    }
}