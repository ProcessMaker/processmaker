<?php

namespace ProcessMaker\Exception;

use Exception;
use Illuminate\Support\Facades\Log;

class CaseException extends Exception
{
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        Log::error('CaseException: ' . $message);
        parent::__construct($message, $code, $previous);
    }
}
