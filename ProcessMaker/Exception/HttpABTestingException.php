<?php

namespace ProcessMaker\Exception;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class HttpABTestingException extends Exception implements HttpExceptionInterface
{
    public $status;

    public $body;

    private $headers;

    public function __construct($message)
    {
        $this->status = 500;
        $this->body = $message;
        $this->headers = [];
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}
