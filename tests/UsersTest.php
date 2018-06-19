<?php

namespace ostkit\test;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ostkit\OstKitClient;

/**
 * Test case for mocked interactions with the '/users' endpoint.
 */
class UsersTest extends TestCase {
    private $ost;

    public function __construct($name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);
        $this->ost = new OstKitMock();
    }

    /**
     * @covers OstKitClient::createUser
     */
    public function testCreateUser() {
        $name = 'Freddy';
        $user = $this->ost->createUser($name);
        self::assertNotNull($user, 'Valid user should not be null.');
        self::assertEquals($name, $user['name']);
    }

    /**
     * @covers OstKitClient::createUser
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Name is mandatory.
     */
    public function testCreateUserNull() {
        $this->ost->createUser(null);
    }

    /**
     * @covers OstKitClient::createUser
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Name must be a minimum of 3 characters, a maximum of 20 characters, and can contain only letters, numbers, and spaces, along with other common sense limitations.
     */
    public function testCreateUserTooShort() {
        $this->ost->createUser('01');
    }

    /**
     * @covers OstKitClient::createUser
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Name must be a minimum of 3 characters, a maximum of 20 characters, and can contain only letters, numbers, and spaces, along with other common sense limitations.
     */
    public function testCreateUserTooLong() {
        $this->ost->createUser('012345678901234567890');
    }

    /**
     * @covers OstKitClient::createUser
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Name must be a minimum of 3 characters, a maximum of 20 characters, and can contain only letters, numbers, and spaces, along with other common sense limitations.
     */
    public function testCreateUserIllegalCharacter() {
        $this->ost->createUser('012345678901234567_/');
    }

    /**
     * @covers OstKitClient::updateUser
     */
    public function testUpdateUser() {
        $olga = $this->ost->createUser('Olga');
        self::assertNotNull($olga, 'Internal disturbance in the force? - valid user should not be null.');
        $name = 'Helga';
        $helga = $this->ost->updateUser($olga['id'], $name);
        self::assertNotNull($helga, 'Valid user should not be null.');
        self::assertEquals($olga['id'], $helga['id']);
        self::assertEquals($name, $helga['name']);
    }

    /**
     * @covers OstKitClient::updateUser
     * @@expectedException InvalidArgumentException
     * @expectedExceptionMessage ID is mandatory.
     */
    public function testUpdateUserNullUuid() {
        $this->ost->updateUser(null, "name");
    }

    /**
     * @covers OstKitClient::updateUser
     * @@expectedException InvalidArgumentException
     * @expectedExceptionMessage Name is mandatory.
     */
    public function testUpdateUserNullName() {
        $olga = $this->ost->createUser('Olga');
        self::assertNotNull($olga, 'Internal disturbance in the force? - valid user should not be null.');
        $this->ost->updateUser($olga['id'], null);
    }

    /**
     * @covers OstKitClient::updateUser
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Name must be a minimum of 3 characters, a maximum of 20 characters, and can contain only letters, numbers, and spaces, along with other common sense limitations.
     */
    public function testUpdateUserNameTooShort() {
        $olga = $this->ost->createUser('name');
        self::assertNotNull($olga, 'Internal disturbance in the force? - valid user should not be null.');
        $this->ost->updateUser($olga['id'], '01');
    }

    /**
     * @covers OstKitClient::updateUser
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Name must be a minimum of 3 characters, a maximum of 20 characters, and can contain only letters, numbers, and spaces, along with other common sense limitations.
     */
    public function testUpdateUserNameTooLong() {
        $olga = $this->ost->createUser('name');
        self::assertNotNull($olga, 'Internal disturbance in the force? - valid user should not be null.');
        $this->ost->updateUser($olga['id'], '012345678901234567890123');
    }

    /**
     * @covers OstKitClient::updateUser
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Name must be a minimum of 3 characters, a maximum of 20 characters, and can contain only letters, numbers, and spaces, along with other common sense limitations.
     */
    public function testUpdateUserNameIllegalCharacter() {
        $this->ost->createUser('012345678901234567_/');
    }

    /**
     * @covers OstKitClient::updateUser
     * @@expectedException Exception
     * @expectedExceptionMessage ID '' is not a valid UUID.
     */
    public function testUpdateUserNonExistent() {
        $this->ost->updateUser('', 'name');
    }

    /**
     * @covers OstKitClient::getUser
     */
    public function testGetUser() {
        $user = $this->ost->createUser('Federica');
        self::assertNotNull($user, 'Internal disturbance in the force? - valid user should not be null.');
        $retrieved = $this->ost->getUser($user['id']);
        self::assertNotNull($retrieved, 'Internal disturbance in the force? - retrieved user should not be null.');
        self::assertEquals($user['id'], $retrieved['id'], 'User ID should be equal.');
        self::assertEquals($user['name'], $retrieved['name'], 'User name should be equal.');
    }

    /**
     * @covers OstKitClient::listUsers
     */
    public function testListUsers() {
        self::assertNotNull($this->ost->createUser('Federica'), 'Internal disturbance in the force? - valid user should not be null.');
        self::assertNotNull($this->ost->createUser('Alessia'), 'Internal disturbance in the force? - valid user should not be null.');
        $users = $this->ost->listUsers();
        self::assertNotNull($users, 'Internal disturbance in the force? - listed users should not be null.');
        self::assertEquals(2, sizeof($users), 'We expected 2 users to be returned');
    }
}