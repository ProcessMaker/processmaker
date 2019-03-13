<?php

namespace ProcessMaker\Exception;

use RuntimeException;

/**
 * Script execution timeout exception.
 *
 */
class ScriptTimeoutException extends RuntimeException
{
    /**
     * @param string $message
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
