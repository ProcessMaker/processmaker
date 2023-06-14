<?php

namespace ProcessMaker\Exception;

use Exception;

/**
 * Thrown if the scripts to a process failed
 */
class RetryableException extends Exception
{
    public $retry_attempts = 0;

    public $retry_wait_time = 0;

    public $original_exception = null;

    public function __construct($message, $errorHandling, $originalException)
    {
        parent::__construct($message);
    }
}
