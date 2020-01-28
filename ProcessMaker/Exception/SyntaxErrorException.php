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
        parent::__construct(__('exceptions.SyntaxErrorException', [
            'error' => $previous->getMessage(),
            'body' => $body
        ]), 0, $previous);
    }
}
