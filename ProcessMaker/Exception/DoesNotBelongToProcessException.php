<?php

namespace ProcessMaker\Exception;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Thrown if the element does not belong to the process.
 *
 * @package ProcessMaker\Exceptions
 */

class DoesNotBelongToProcessException extends HttpException
{

    public function __construct(string $message = null, Exception $previous = null, array $headers = array(), ?int $code = 0, $statusCode = 404)
    {
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }

}
