<?php

namespace ostkit\test;

use Exception;

/**
 * Mock class for the '/actions' endpoint.
 *
 * @package ostkit\test
 */
class BalanceServiceMock extends AbstractServiceMock {
    const UUID = 'f5f9b061-b784-4ecd-b599-bc263860f539';
    private $json;

    function __construct() {
        parent::__construct('/balance');
        $this->json = json_decode('{
  "success": true,
  "data": {  
      "result_type": "balance",
      "balance":  {  
         "available_balance": "14.243366506781137",
         "airdropped_balance": "6.231683253390568746",
         "token_balance": "8.011683253390568746"
      }
   }

}', true);
    }

    function get($id, $arguments, $fetchAll, $extractResultTYpe) {
        if (isset($id) && $id === self::UUID) { // retrieve
            return $this->json;
        }
        throw new Exception('Resource matching the id could not be located.');
    }
}