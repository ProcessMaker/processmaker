<?php

namespace ProcessMaker\Exception;

use RuntimeException;
use Throwable;

class DevLinkRemoteValidationException extends RuntimeException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
