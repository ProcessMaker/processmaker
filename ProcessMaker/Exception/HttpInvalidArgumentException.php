<?php

namespace ProcessMaker\Exception;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Thrown if an URL expression failed to be parsed
 */
class HttpInvalidArgumentException extends Exception implements HttpExceptionInterface
{
    public $status;

    public $body;

    private $headers;

    /**
     * @param string $message
     */
    public function __construct($message)
    {
        $this->status = 500;
        $this->body = $message;
        $this->headers = [];
        parent::__construct($message);
    }

    public function getStatusCode()
    {
        return $this->status;
    }

    public function getHeaders()
    {
        return $this->headers;
    }
}
