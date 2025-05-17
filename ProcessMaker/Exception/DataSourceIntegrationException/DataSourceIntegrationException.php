<?php

namespace ProcessMaker\Exception\DataSourceIntegrationException;

use Exception;

/**
 * Thrown when an unsupported data source is requested
 */
class DataSourceIntegrationException extends Exception
{
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
