<?php

namespace ProcessMaker\Exception;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Thrown if the Assignee user or groups to a task failed
 *
 * @package ProcessMaker\Exceptions
 */

class TaskAssignedException extends HttpException
{

    public function __construct(string $message = null, Exception $previous = null, array $headers = array(), ?int $code = 0, $statusCode = 404)
    {
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }

}
