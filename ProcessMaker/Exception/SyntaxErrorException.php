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
    public function __construct(Throwable $previous, String $body, $data)
    {
        $message = $previous->getMessage();
        if (preg_match('/Variable "(\w+)"/', $message, $match)) {
            if (!isset($data[$match[1]])) {
                $message = __('Undefined variable ":variable". :error', [
                    'variable' => $match[1],
                    'error' => $message,
                ]);
            }
        }
        parent::__construct($message, 0, $previous);
    }
}
