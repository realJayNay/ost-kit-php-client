<?php

namespace Ost\Kit\Php\Client;

use Exception;
use InvalidArgumentException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * OST Kit PHP client
 */
class OstKitClient {
    private $baseUrl; // OST REST base URL
    private $apiKey; // OST KIT API key
    private $apiSecret; // OST KIT API secret

    private $log;

    private $token;
    private $cache;

    /**
     * Static factory for OstKitClient instances. Creates a new OST KIT PHP client using your API key and secret.
     *
     * @param string $apiKey OST API key (mandatory)
     * @param string $apiSecret OST KIT API secret (mandatory)
     * @param string $baseUrl OST REST base URL
     * @param bool $debug Enable debug logging to php://stdout (defaults to true)
     * @return OstKitClient
     * @throws InvalidArgumentException when API key, API secret and/or Base URL is missing
     * @throws Exception when initialization fails
     */
    public static function create($apiKey, $apiSecret, $baseUrl = 'https://sandboxapi.ost.com/v1', $debug = true) {
        if (!isset($apiKey) || !isset($apiSecret)) {
            throw new InvalidArgumentException('API Key and API Secret are mandatory.');
        }
        if (!isset($baseUrl)) {
            throw new InvalidArgumentException('Base URL is mandatory.');
        }
        $ost = new OstKitClient($apiKey, $apiSecret, $baseUrl, $debug,'user');
        $ost->init();
        return $ost;
    }

    protected function __construct($apiKey, $apiSecret, $baseUrl, $debug, ...$caches) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->baseUrl = $baseUrl;
        $this->cache = array_fill_keys($caches, array());
        $this->log = new Logger('OstKitClient');
        try {
            $this->log->pushHandler(
                new StreamHandler('php://stderr', Logger::WARNING)
            );
            if ($debug) {
                $this->log->pushHandler(
                    new StreamHandler('php://stdout', Logger::DEBUG)
                );
            }
        } catch (Exception $ignored) {
            $this->log->warn('Unable to set stream handlers for stderr and stdout. Falling back to default monolog configuration.');
        }
    }

    private function init() {
        $this->log->debug('Initialized result type caches', $this->cache);
        $this->log->debug('Checking OST KIT connectivity and retrieving Branded Token details.');
        $this->token = $this->getToken();
        $this->log->info('Branded Token economy is open for business.', $this->token);
    }

    /**
     * Create a user with the given name.
     *
     * @param string $name User Name (mandatory, not unique) - must be a minimum of 3 characters, a maximum of 20 characters, and can contain only letters, numbers, and spaces, along with other common sense limitations.
     * @return array decoded JSON array of the 'user' result type
     * @throws InvalidArgumentException when the ID is missing or the Name does not pass validation
     * @throws Exception when the HTTP call is unsuccessful
     */
    public function createUser($name) {
        self::validateName($name);
        $user = $this->post('/users', array('name' => $name));
        $this->log->info('Created user', $user);
        return $user;
    }

    /**
     * Rename an existing user.
     *
     * @param string $id User ID (mandatory)
     * @param string $name User Name (mandatory) - must be a minimum of 3 characters, a maximum of 20 characters, and can contain only letters, numbers, and spaces, along with other common sense limitations.
     * @return array decoded JSON array of the updated 'user' result type
     * @throws InvalidArgumentException when the ID is missing or the Name does not pass validation
     * @throws Exception when the HTTP call is unsuccessful
     */
    public function updateUser($id, $name) {
        self::validateId($id);
        self::validateName($name);
        $user = $this->post("/users/$id", array('name' => $name));
        $this->log->info('Updated user', $user);
        return $user;
    }

    /**
     * Retrieve an existing user.
     *
     * @param string $id User ID (mandatory)
     * @return array decoded JSON array of the updated 'user' result type
     * @throws InvalidArgumentException when the ID is missing or the Name does not pass validation
     * @throws Exception when the HTTP call is unsuccessful
     */
    public function getUser($id) {
        self::validateId($id);
        $user = $this->get("/users/$id", false);
        $this->log->info('Retrieved user', $user);
        return $user;
    }

    public function listUsers($fetchAll = false, $filters = array(), $page = 1, $airdropped = null, $orderBy = 'created', $order = 'desc', $limit = 100) {
        $params = array('page_no' => $page, 'order' => $order, 'limit' => $limit, 'order_by' => $orderBy);
        if (isset($airdropped)) {
            $params['airdropped'] = $airdropped ? 'true' : 'false';
        }
        if (isset($filters)) {
            if (isset($filters['id'])) {
                $params['optional_filters'] = 'id=' . implode(',', $filters['id']);
            } else {
                if (isset($filters['name'])) {
                    $params['optional_filters'] = 'name=' . implode(',', $filters['name']);
                }
            }
        }
        $users = $this->get('/users', $fetchAll, $params);
        $this->log->info('Listed ' . sizeof($users) . ' users', $users);
        return $users;
    }

    /**
     * Create an action.
     *
     * @param string $name Unique Name (mandatory, unique) - must be a minimum of 3 characters, a maximum of 20 characters, and can contain only letters, numbers, and spaces, along with other common sense limitations.
     * @param string $kind Kind (mandatory) - must be one of 'company_to_user', 'user_to_company' or 'user_to_user'
     * @param string $currency Currency (mandatory) - either 'USD' (fixed) or 'BT' (floating)
     * @param double $amount Amount - 0.00001 to 100 for 'BT' or 0.01 to 100 for 'USD' - null makes the action accept arbitrary amounts
     * @param double $commissionPercent Commission Fee (only for 'user_to_user' actions) - percentage inclusive in the amount - 0 to 100  - null makes this action accept arbitrary commissions
     * @return array decoded JSON array of the updated 'action' result type
     * @throws InvalidArgumentException when the input parameters do not pass validation
     * @throws Exception when the HTTP call is unsuccessful
     */
    public function createAction($name, $kind, $currency = 'BT', $amount = null, $commissionPercent = null) {
        self::validateName($name);
        self::validateKind($kind);
        self::validateAmount($amount, $currency, false, true);
        $params = array('name' => $name, 'kind' => $kind, 'currency' => $currency);

        if (!isset($amount)) {
            $params['arbitrary_amount'] = 'true';
        } else {
            self::validateAmount($amount, $currency);
            $params['amount'] = $amount;
        }
        if ($kind == 'user_to_user') {
            if (!isset($commissionPercent)) {
                $params['arbitrary_commission'] = 'true';
            } else {
                self::validateNumber($commissionPercent, 0);
                $params['commission_percent'] = $commissionPercent;
                $params['arbitrary_commission'] = 'false';
            }
        }
        $action = $this->post('/actions', $params);
        $this->log->info('Created action', $action);
        return $action;
    }

    /**
     * Update an existing action.
     *
     * @param string $id Action ID (mandatory)
     * @param string $name Name (unique) - must be a minimum of 3 characters, a maximum of 20 characters, and can contain only letters, numbers, and spaces, along with other common sense limitations.
     * @param string $kind Kind - cannot be changed after creation - used to validate and add commission percent only for 'user_to_user' actions
     * @param string $currency Currency (mandatory) - either 'USD' (fixed) or 'BT' (floating)
     * @param double|null $amount Amount - 0.00001 to 100 for 'BT' or 0.01 to 100 for 'USD' - null makes the action accept arbitrary amounts
     * @param double|null $commissionPercent Commission Fee (only for 'user_to_user' actions) - percentage inclusive in the amount - 0 to 100  - null makes this action accept arbitrary commissions
     * @return array decoded JSON array of the updated 'action' result type
     * @throws InvalidArgumentException when the input parameters do not pass validation
     * @throws Exception when the HTTP call is unsuccessful
     */
    public function updateAction($id, $name = null, $kind = null, $currency = null, $amount = null, $commissionPercent = null) {
        self::validateId($id);
        $params = array();
        if (isset($name)) {
            self::validateName($name, false);
            $params['name'] = $name;
        }
        if (isset($currency)) {
            self::validateAmount($amount, $currency, false);
            $params['currency'] = $currency;
        }
        if (!isset($amount)) {
            $params['arbitrary_amount'] = 'true';
        } else {
            self::validateAmount($amount, $currency, false, false);
            $params['amount'] = $amount;
            $params['arbitrary_amount'] = 'false';
        }
        if (isset($kind) && $kind == 'user_to_user') {
            if (!isset($commissionPercent)) {
                $params['arbitrary_commission'] = 'true';
            } else {
                self::validateNumber($commissionPercent, 0);
                $params['commission_percent'] = $commissionPercent;
                $params['arbitrary_commission'] = 'false';
            }
        }
        $action = $this->post("/actions/$id", $params);
        $this->log->info('Updated action', $action);
        return $action;
    }

    /**
     * Retrieve an existing action.
     *
     * @param string $id Action ID (mandatory)
     * @return array decoded JSON array of the updated 'action' result type
     * @throws InvalidArgumentException when the ID is missing
     * @throws Exception when the HTTP call is unsuccessful
     */
    public function getAction($id) {
        self::validateId($id);
        $action = $this->cache['action'][$id];
        if (isset($action)) {
            $this->log->debug("Using cached value for action $id", $action);
        } else {
            $action = $this->get("/actions/$id", false);
            $this->log->info('Retrieved action', $action);
        }
        return $action;
    }

    public function listActions($fetchAll = false, $filters = array(), $page = 1, $orderBy = 'created', $order = 'desc', $limit = 100) {
        $params = array('page_no' => $page, 'order' => $order, 'limit' => $limit, 'order_by' => $orderBy);
        if (isset($airdropped)) {
            $params['airdropped'] = $airdropped ? 'true' : 'false';
        }
        if (isset($filters)) {
            if (isset($filters['id'])) {
                $params['optional_filters'] = 'id=' . implode(',', $filters['id']);
            } else {
                if (isset($filters['name'])) {
                    $params['optional_filters'] = 'name=' . implode(',', $filters['name']);
                } else {
                    if (isset($filters['kind'])) {
                        $params['optional_filters'] = 'kind=' . implode(',', $filters['kind']);
                    } else {
                        if (isset($filters['arbitrary_amount'])) {
                            if ($filters['arbitrary_amount']) {
                                $params['optional_filters'] = 'arbitrary_amount=true';
                            } else {
                                $params['optional_filters'] = 'arbitrary_amount=false';
                            }
                        } else {
                            if (isset($filters['arbitrary_commission'])) {
                                if ($filters['arbitrary_commission']) {
                                    $params['optional_filters'] = 'arbitrary_commission=true';
                                } else {
                                    $params['optional_filters'] = 'arbitrary_commission=false';
                                }
                            }
                        }
                    }
                }
            }
        }
        $actions = $this->get('/actions', $fetchAll, $params);
        $this->log->info('Listed ' . sizeof($actions) . ' actions', $actions);
        return $actions;
    }

    /**
     * Execute an existing 'user_to_company' or 'company_to_user' action and automatically assign the company as recipient or sender.
     *
     * This method retrieves the action first to:
     *  - decide whether to assign the company as recipient or sender of the action
     *  - validate the amount against the currency of the action
     *
     * @param string $id Action ID (mandatory)
     * @param string $userId User ID (mandatory)
     * @param double|null $amount Amount - 0.00001 to 100 for 'BT' or 0.01 to 100 for 'USD' - null if the amount is preset by the action
     * @return array decoded JSON array of the updated 'transaction' result type
     * @throws InvalidArgumentException when the requested action is 'user_to_user' or the input parameters do not pass validation
     * @throws Exception when the HTTP call is unsuccessful
     */
    public function executeCompanyAction($id, $userId, $amount = null) {
        self::validateId($id);
        $action = $this->getAction($id);
        self::validateKind($action['kind']);
        if (strpos($action['kind'], 'company') == false) {
            throw new InvalidArgumentException("Action $id does not involve the company");
        }
        if (isset($amount)) {
            if ($action['arbitrary_amount'] != 'true') {
                throw new InvalidArgumentException("Action $id does not accept arbitrary amounts.");
            }
            self::validateAmount($amount, $action['currency']);
        }
        if ($action['kind'] == 'company_to_user') {
            return $this->executeAction($id, $this->token['company_uuid'], $userId, $amount);
        }
        return $this->executeAction($id, $userId, $this->token['company_uuid'], $amount);
    }

    /**
     * Execute any existing action.
     *
     * @param string $id Action ID (mandatory)
     * @param string $from Sending User ID (mandatory)
     * @param string $to Receiving User ID (mandatory)
     * @param double|null $amount Amount - 0.00001 to 100 for 'BT' or 0.01 to 100 for 'USD' - null if the amount is preset by the action
     * @param double|null $commissionPercent Commission Fee (only for 'user_to_user' actions) - percentage inclusive in the amount - 0 to 100  - null if the commission percentage is preset by the action
     * @return array decoded JSON array of the updated 'transaction' result type
     * @throws InvalidArgumentException when the input parameters do not pass validation
     * @throws Exception when the HTTP call is unsuccessful
     */
    public function executeAction($id, $from, $to, $amount = null, $commissionPercent = null) {
        self::validateId($id);
        if (!isset($from) || !isset($from)) {
            throw new InvalidArgumentException('From ID and To ID are mandatory.');
        }
        $params = array('from_user_id' => $from, 'to_user_id' => $to, 'action_id' => $id);
        if (isset($amount)) {
            self::validateAmount($amount, null, true, false);
            $params['amount'] = $amount;
        }
        if (isset($commissionPercent)) {
            self::validateNumber($commissionPercent, 0);
            $params['commission_percent'] = $commissionPercent;
        }
        $transaction = $this->post('/transactions', $params);
        $this->log->info('Executed action', $transaction);
        return $transaction;
    }

    /**
     * Retrieve an existing transaction.
     *
     * @param string $id Transaction ID (mandatory)
     * @return array decoded JSON array of the 'transaction' result type (an additional attribute 'view_url' is added that contains a hyperlink to OST View)
     * @throws InvalidArgumentException when the ID is missing
     * @throws Exception when the HTTP call is unsuccessful
     */
    public function getTransaction($id) {
        self::validateId($id);
        $transaction = $this->get("/transactions/$id", false);
        $hash = $transaction['transaction_hash'];
        if (isset($hash)) {
            $transaction['view_url'] = 'https://view.ost.com/chain-id/' . $this->token['ost_utility_balance'][0][0] . "/transaction/$hash";
        }
        $this->log->debug('Retrieved transaction', $transaction);
        return $transaction;
    }

    public function listTransactions($fetchAll = false, $filters = array(), $page = 1, $order = 'desc', $limit = 100) {
        $params = array('page_no' => $page, 'order' => $order, 'limit' => $limit);
        if (isset($filters) && isset($filters['id'])) {
            $params['optional_filters'] = 'id=' . implode(',', $filters['id']);
            $this->log->debug('Imploded optional_filters to ' . $params['optional_filters']);
        }
        $transactions = $this->get('/transactions', $fetchAll, $params);
        $this->log->info('Listed ' . sizeof($transactions) . ' transactions', $transactions);
        return $transactions;
    }

    /**
     * Execute an airdrop to provide a certain amount of Branded Tokens to a set of users.
     *
     * Please note the interdependency between the $airdropped and $userIds parameters.
     *
     * @param int $amount Number (mandatory) of Branded Tokens to airdrop - must be a positive integer value that is less than the total supply
     * @param boolean|null $airdropped Indicates whether to airdrop tokens to end-users who have been airdropped some tokens at least once (true) or to end-users who have never (false) been airdropped tokens or null to omit this filter
     * @param array $userIds Array of selected User IDs to airdrop tokens to or null to omit this filter - this filter works on top of the $airdropped filter
     * @return array decoded JSON array of the 'airdrop' result type
     * @throws InvalidArgumentException when the $amount is missing
     * @throws Exception when the HTTP call is unsuccessful
     */
    public function airdrop($amount, $airdropped = null, $userIds = array()) {
        self::validateAmount($amount, 0, $this->token['total_supply']);
        $params = array('amount' => $amount);
        if (isset($userIds) && sizeof($userIds) > 0) {
            $params['user_ids'] = implode(',', $userIds);
        }
        if (isset($airdropped)) {
            $params['airdropped'] = $airdropped;
        }
        $airdrop = $this->post('/airdrops', $params);
        $this->log->info('Executed airdrop', $airdrop);
        return $airdrop;
    }

    /**
     * Retrieves a previously scheduled
     *
     * Please note the interdependency between the $airdropped and $userIds parameters.
     *
     * @param string $id Airdrop ID
     * @return array decoded JSON array of the 'airdrop' result type
     * @throws InvalidArgumentException when the $amount is missing
     * @throws Exception when the HTTP call is unsuccessful
     */
    public function getAirdrop($id) {
        self::validateId($id);
        $airdrop = $this->get("/airdrops/$id", false);
        $this->log->info('Retrieved airdrop', $airdrop);
        return $airdrop;
    }

    public function listAirdrops($fetchAll = false, $page = 1, $filter = 'all', $orderBy = 'created', $order = 'desc', $limit = 10, $optionalFilters = '') {
        $users = $this->get('/users', $fetchAll, array('page_no' => $page, 'filter' => $filter, 'order_by' => $orderBy, 'order' => $order, 'limit' => $limit, 'optional_filters' => $optionalFilters));
        $this->log->debug("Listed users", $users);
        return $users;
    }

    /**
     * Transfer an arbitrary amount of OST⍺ Prime to an account outside of your Branded Token economy.
     *
     * @param string $toAddress Public address (mandatory) to which to transfer OST⍺ Prime
     * @param int $amount Amount (mandatory) of OST⍺ Prime to transfer in Wei - between 0 and 10^20 (exclusive)
     * @return array decoded JSON array of the 'transfer' result type
     * @throws InvalidArgumentException when the $amount is missing
     * @throws Exception when the HTTP call is unsuccessful
     */
    public function transfer($toAddress, $amount) {
        self::validateIsset($toAddress, 'To Address');
        self::validateNumber($amount, 0, 10 ^ 20 - 1);
        $transfer = $this->post('/transfers', array('to_address' => $toAddress, 'amount' => $amount));
        $this->log->debug('Created OST⍺ Prime transfer', $transfer);
        return $transfer;
    }

    /**
     * Retrieves a transfer.
     *
     * @param string $id Transaction ID
     * @return array decoded JSON array of the 'transfer' result type
     * @throws InvalidArgumentException when the $id is missing
     * @throws Exception when the HTTP call is unsuccessful
     */
    public function getTransfer($id) {
        self::validateId($id);
        $transfer = $this->get("/transfers/$id", false);
        $this->log->debug('Retrieved OST⍺ Prime transfer', $transfer);
        return $transfer;
    }

    public function listTransfers($fetchAll = false, $page = 1, $filter = 'all', $orderBy = 'created', $order = 'desc', $limit = 10, $optionalFilters = '') {
        $users = $this->get('/transfers', $fetchAll, array('page_no' => $page, 'filter' => $filter, 'order_by' => $orderBy, 'order' => $order, 'limit' => $limit, 'optional_filters' => $optionalFilters));
        $this->log->debug("Listed OST⍺ Prime transfers", $users);
        return $users;
    }

    /**
     * Retrieves the Branded Token details.
     *
     * @return array decoded JSON array of the 'token' result type
     * @throws Exception
     */
    public function getToken() {
        $token = $this->get('/token', false);
        $this->log->debug("Retrieved token", $token);
        return $token;
    }

    /**
     * Retrieves the current USD price of OST.
     *
     * @return array decoded JSON array fo currency/price pairs (at least 'OST/USD')
     * @throws Exception
     */
    public function getOstPricePoints() {
        $json = $this->get('/token', false, array(), false);
        $this->log->debug("Retrieved OST price points", $json['data']['price_points']);
        return $json['data']['price_points'];
    }

    protected function get($endpoint, $fetchAll, $arguments = array(), $extractResultType = true) {
        if ($fetchAll && isset($arguments['limit'])) {
            $arguments['limit'] = 100; // increase limit to max for fetch-all
        }
        $arguments['api_key'] = $this->apiKey;
        $arguments['request_timestamp'] = time();
        ksort($arguments);
        foreach ($arguments as $key => $value) {
            $value = urlencode(str_replace(' ', '+', $value));
            $arguments[$key] = $value;
        }

        $query = $endpoint . '?' . http_build_query($arguments, '', '&');
        $url = $this->baseUrl . $query . '&signature=' . hash_hmac('sha256', $query, $this->apiSecret);
        $this->log->debug("GET $url");
        $json = file_get_contents($url);

        if ($json == FALSE) {
            throw new Exception("GET request failed: $url");
        }
        $this->log->debug("JSON $json");
        $jsonArray = json_decode($json, true);
        if (!$jsonArray['success']) {
            throw new Exception("GET request unsuccessful: '" . $jsonArray['err']['msg'] . "': $url");
        }

        if ($fetchAll && isset($jsonArray['data']['meta']['next_page_payload']['page_no'])) {
            // recursively fetch all items
            $nextPage = $jsonArray['data']['meta']['next_page_payload']['page_no'];
            while (isset($nextPage) && $nextPage != $arguments['page_no']) {
                $this->log->debug("fetching page $nextPage");
                $arguments['page_no'] = $nextPage;
                $add = $this->get($endpoint, $fetchAll, $arguments);
                $jsonArray = array_merge_recursive($jsonArray, $add);
            }
        }
        return $extractResultType ? $this->extractResultType($jsonArray) : $jsonArray;
    }

    protected function post($endpoint, $arguments = array(), $extractResultType = true) {
        $arguments['api_key'] = $this->apiKey;
        $arguments['request_timestamp'] = time();
        ksort($arguments);
        $query = $endpoint . '?' . http_build_query($arguments, '', '&');
        $query = str_replace('%5B%5D', '[]', $query);
        $query = str_replace('%20', '+', $query);
        $arguments['signature'] = hash_hmac('sha256', $query, $this->apiSecret);
        $encoded = http_build_query($arguments);
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $encoded
            )
        ));

        $this->log->debug("POST $this->baseUrl$endpoint with body $encoded");
        $json = file_get_contents($this->baseUrl . $endpoint, FALSE, $context);

        if ($json == FALSE) {
            throw new Exception("POST request failed: $this->baseUrl$endpoint with body $encoded");
        }
        $this->log->debug("JSON $json");
        $jsonArray = json_decode($json, true);
        if (!$jsonArray['success']) {
            throw new Exception($jsonArray['err']['msg']);
        }
        return $extractResultType ? $this->extractResultType($jsonArray) : $jsonArray;
    }

    private function extractResultType($jsonArray) {
        if (isset($jsonArray['data']) && isset($jsonArray['data']['result_type'])) {
            $resultType = $jsonArray['data']['result_type'];
            $this->log->debug("Extracting result type", array($jsonArray['data']['result_type']));
            $cacheKey = $resultType;
            if (substr($resultType, -1) == 's') {
                $cacheKey = substr($resultType, 0, -1);
            }
            if (array_key_exists($this->cache, $resultType) && isset($jsonArray['data'][$resultType]['id'])) {
                $this->log->debug("Caching result", array($jsonArray['data']['result_type']));
                if (sizeof($jsonArray['data'][$resultType]) > 1) {
                    // cache each item for list result types
                    foreach ($jsonArray['data'][$resultType] as $item) {
                        $this->cache[$cacheKey][$jsonArray['data'][$resultType]['id']] = $item['id'];
                    }
                } else {
                    $this->cache[$cacheKey][$jsonArray['data'][$resultType]['id']] = $jsonArray['data'][$resultType]['id'];
                }
            }
            return $jsonArray['data'][$resultType];

        }
        return reset($jsonArray);
    }

    private static function validateName($name, $nameRequired = true, $min = 3, $max = 20, $regex = '/^[a-zA-Z0-9 ]+$/') {
        if ($nameRequired) {
            self::validateIsset($name, 'Name');
        }
        if (isset($name)) {
            if (strlen($name) < $min || strlen($name) > $max || !preg_match($regex, $name) == 1) {
                throw new InvalidArgumentException("Name must be a minimum of $min characters, a maximum of $max characters, and can contain only letters, numbers, and spaces, along with other common sense limitations.");
            }
        }
        return true;
    }

    private static function validateKind($kind) {
        return self::validateOneOf($kind, 'Action Kind', 'user_to_user', 'company_to_user', 'user_to_company');
    }

    private static function validateAmount($amount, $currency, $amountRequired = true, $currencyRequired = true) {
        if ($currencyRequired) {
            self::validateIsset($currency, 'Currency');
        }
        if ($amountRequired) {
            self::validateIsset($amount, 'Amount');
        }
        if (isset($currency)) {
            self::validateOneOf($currency, 'Currency', 'BT', 'USD');
        }
        if (isset($amount)) {
            $min = 0;
            if ($currency == 'BT') {
                $min = 0.00001;
            } else {
                if ($currency == 'USD') {
                    $min = 0.01;
                }
            }
            self::validateNumber($amount, $min);
        }
        return true;
    }

    private static function validateNumber($number, $min, $max = 100, $required = true) {
        if ($required) {
            self::validateIsset($number, 'Number');
        }
        if (isset($number) && ($number < $min || $number > $max)) {
            throw new InvalidArgumentException("Number value must be between $min and $max.");
        }
        return true;
    }

    private static function validateId($id) {
        return self::validateIsset($id, 'ID');
    }

    private static function validateIsset($ref, $subject = 'ID') {
        if (!isset($ref)) {
            throw new InvalidArgumentException("$subject is mandatory.");
        }
        return true;
    }

    private static function validateOneOf($input, $subject, ...$values) {
        if (isset($input)) {
            foreach ($values as $value) {
                if (strcmp($input, $value) == 0) {
                    return true;
                }
            }
        }
        throw new InvalidArgumentException("$subject has an invalid value '$input'. Possible values are: $values.");
    }
}
