# OST KIT - PHP Wrapper for the OST KIT API

[![Latest Stable Version](https://img.shields.io/packagist/v/jay-nay/ost-kit-php-client.svg)](https://packagist.org/packages/jay-nay/ost-kit-php-client#v0.9.2)
[![Latest Unstable Version](https://img.shields.io/packagist/vpre/jay-nay/ost-kit-php-client.svg)](https://packagist.org/packages/jay-nay/ost-kit-php-client#dev-master)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg?style=flat-square)](https://php.net/)
[![Build Status](https://travis-ci.org/realJayNay/ost-kit-php-client.svg?branch=master)](https://travis-ci.org/realJayNay/ost-kit-php-client)
[![codecov](https://codecov.io/gh/realJayNay/ost-kit-php-client/branch/master/graph/badge.svg)](https://codecov.io/gh/realJayNay/ost-kit-php-client)
	
A PHP wrapper for the REST API of [OST KIT](https://kit.ost.com) which is currently under active development. This client implements version 1.0 of the [OST KIT REST API](https://dev.ost.com).

Older versions of this plugin can be found in the [releases](https://github.com/realJayNay/ost-kit-php-client/releases) overview.

![Screenshot](ostkit.png)

A Branded Token economy must be setup first in order to use the API, see https://kit.ost.com for more information.

## Installation

Install the latest version with

```bash
$ composer require jay-nay/ost-kit-php-client
```

This package is on [packagist](https://packagist.org/packages/jay-nay/ost-kit-php-client). 
To get the latest version add the following dependency to your ```composer.json```.

```
require": {
    "jay-nay/ost-kit-php-client": "dev-master"
}
```

## How to use the client

Create the OST KIT client using your Branded Token economy's `API key` and `API secret`.
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use ostkit\OstKitClient;

$ost = OstKitClient::create('YOUR-API-KEY', 'YOUR-API-SECRET');

// Create a user named 'Louise',
$user = $ost->createUser('Louise');

// ... and rename her to 'Liza'.
$user = $ost->updateUser($user['id'], 'Liza');

// Get information about a specific user, including its public address and token balance.
$user = $ost->getUser($user['id']);

// List all users.
$users = $ost->listUsers(true);

// Create a user-to-user action that transfers an arbitrary amount of Branded Tokens to a user.
$action = $ost->createAction('BT fund transfer3', 'user_to_user', 'BT', null, 0.0);

// Update an action, the kind of an action is specified when it is created an cannot change.
// Change the action currency to 'USD', amount is still arbitrary and a commission of 1.5 percent will be charged.
// Commission charges can only be specified for user-to-user actions.
$action = $ost->updateAction($action['id'], null, $action['kind'], 'USD', null, 1.5);

// Get information about a specific action.
$action = $ost->getAction($action['id']);

// Lists some actions.
$actions = $ost->listActions(false);

// Execute an action.
$transaction = $ost->executeAction($action['id'], '2e6c0d8f-29b0-41fe-b7c0-83d9eb043fe1', '6c17aca7-9911-4dc0-8aa6-30dedae4d73d', 1);

// Get the information of an executed transaction.
$transaction = $ost->getTransaction($transaction['id']);

// List some transactions for the users in the array.
$transactions = $ost->listTransactions(false, array('id' => array('4e84c205-7da4-4547-b400-fa40e979227b')));

// Transfer some OST⍺ Prime (in Wei).
$transfer = $ost->transfer('0x495ed9A80b429C4A1eA678988ffBE5685D64fF99', 1);

// Retrieve a transfer.
$transfer = $ost->getTransfer('4073cd70-e4b8-44e9-96ad-871dd8c1e70f');

// List all transfers
$tansfers = $ost->listTransfers(true);

// more examples to come; have a look at the phpdoc in the meanwhile :)
```

## Using the library via packagist

This client is also available via [packagist](https://packagist.org/packages/jay-nay/ost-kit-php-client). 
See [here](https://getcomposer.org/doc/00-intro.md) for an introduction on how to manage dependencies via ```composer.json```.

## OST KIT PHP Client Roadmap

Some things to do, and ideas for potential features:

* Improve the **performance** of the web client by making asynchronous, multi-threaded web calls.
* Improve the **efficiency** of the web client by fully supporting arrays as input type where applicable.
* ~~Automatically derive which JSON sub-array to return based on the `result_type` attribute of the web response.~~
* ~~Fully document the API and all function parameters and return types.~~
* ~~Automatically assign the _company_ as debtor in `company_to_user` and as creditor in `user_to_company` transaction types.~~
* ~~Implement the retrieval of a users's _wallet address_ based on its `UUID` in OST KIT, so it can be view directly in [OST View](https://view.ost.com).~~

## Questions, feature requests and bug reports
If you have questions, have a great idea for the client or ran into issues using this client: please report them in the project's [Issues](https://github.com/realJayNay/ost-kit-php-client/issues) area.  

Brought to you by [Jay Nay](https://github.com/realJayNay)






