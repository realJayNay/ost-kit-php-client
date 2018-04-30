# OST KIT - PHP Wrapper for the OST KIT API

A PHP wrapper for the REST API of [OST KIT](https://kit.ost.com) which is currently under active development. This client implements version 0.9.2 of the [OST KIT REST API](https://dev.ost.com).

![Screenshot](ostkit.png)

A Branded Token economy must be setup first in order to use the API, see https://kit.ost.com for more information.

## How to use the client

Create the OST KIT client using your Branded Token economy's `API key` and `API secret`.
```php
$ost = OstKitClient::create('YOUR-API-KEY', 'YOUR-API-SECRET');


/* USERS functions */
// Create a user named 'Ria'.
$user = $ost->createUser('Ria');

// ... and rename 'Ria' to 'Fred'.
$user = $ost->editUser($user['uuid'], 'Fred');

// List users. Either the first 25 of the result set, or optionally fetch all users by recursively consuming all result set pages.
$firstUsers = $ost->listUsers(); // lists first page of 25 users
$users = $ost->listUsers(true); // lists all users


/* TRANSACTION TYPES functions */
// Create a transaction type.
$transactionType = $ost->createTransactionType('Clap', 'user_to_user', 1); // user_to_user transaction of 1 BT named 'Clap'

// List transaction types.
$transactionTypes = $ost->listTransactionTypes(true); // lists all transaction types

// Execute a transaction type. This transfers a preconfigured amount of Branded Tokens from a user or company to another user or company.
$transaction = $ost->executeTransactionType($fromUuid, $toUuid, $transactionKind);

// ... and retrieve the status of the transaction. Allow the transaction some time to get processed on the OpenST utility chains. 
$status = $ost->getTransactionStatus($transaction['transaction_uuid']);


/* AIRDROP functions */
// Airdrop tokens either to all users or only to the users that have never been airdropped before.
$aidropUuid = $ost->airdrop(1, 'all'); // airdrop 1 token to all users

// Retrieve the status of the airdrop. 
// Warning - This functionality currently fails, see https://github.com/realJayNay/ost-kit-php-client/issues/1.
$airdropStatus = $ost->getAirdropStatus($airdropUuid);


/* NON-API functions */

// Retrieve a single user's token balance. This is not implemented by the OST KIT API, but is done by looping over all users until a match is found.
$tokenBalance = $ost->getUserTokenBalance($uuid);
```

## OST KIT PHP Client Roadmap

Some things to do, and ideas for potential features:

* Improve the **performance** of the web client by making asynchronous, multi-threaded web calls.
* Improve the **efficiency** of the web client by fully supporting arrays as input type.
* Automatically derive which JSON sub-array to return based on the `result_type` attribute of the web response.
* Fully document the API and all function parameters and return types.
* Automatically assign the _company_ as debtor in `company_to_user` and as creditor in `user_to_company` transaction types.
* Implement the retrieval of a users's _wallet address_ based on its `UUID` in OST KIT, so it can be view directly in [OST View](https://view.ost.com).

## Questions, feature requests and bug reports
If you have questions, have a great idea for the client or ran into issues using this client: please report them in the project's [Issues](https://github.com/realJayNay/ost-kit-php-client/issues) area.  

Brought to you by [Jay Nay](https://github.com/realJayNay)






