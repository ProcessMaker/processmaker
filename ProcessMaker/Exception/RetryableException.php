<?php

namespace ProcessMaker\Exception;

use Exception;

/**
 * Thrown if the scripts to a process failed
 */
class RetryableException extends Exception
{

    public function __construct(public $retry_wait_time)
    {
        parent::__construct();
    }
}
