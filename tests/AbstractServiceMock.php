<?php


namespace ostkit\test;

/**
 * Abstract base class for service mock classes.
 *
 * @package ostkit\test
 */
abstract class AbstractServiceMock {
    private $endpoint;

    protected function __construct($endpoint) {
        $this->endpoint = $endpoint;
    }

    /**
     * Filter method that checks if the specified endpoint matches this service's endpoint.
     *
     * @param string $endpoint runtime endpoint as requested by the ost kit client
     * @return bool whether this service can handle interactions with the specified endpoint
     */
    function accepts($endpoint) {
        return isset($endpoint) && strpos($endpoint, $this->endpoint) === 0;
    }

    abstract function get($id, $arguments, $fetchAll, $extractResultTYpe);

    function post($id, $arguments, $extractResultType) {
    }
}