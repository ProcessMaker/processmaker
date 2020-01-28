<?php

namespace ProcessMaker\Exception;

use Exception;
use Throwable;

/**
 * Thrown if an expression failed to be parsed
 *
 * @package ProcessMaker\Exceptions
 */

class SyntaxErrorException extends Exception
{

    /**
     * @param Throwable $previous
     */
    public function __construct(Throwable $previous, String $body)
    {
        parent::__construct(__('The expression ":body" is invalid. Please contact the creator of this process to fix the issue. Original error: ":error"', [
            'error' => $previous->getMessage(),
            'body' => $body
        ]), 0, $previous);
    }
}
