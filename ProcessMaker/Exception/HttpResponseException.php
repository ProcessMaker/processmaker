<?php

namespace ProcessMaker\Exception;

use Exception;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Thrown if an expression failed to be parsed
 */
class HttpResponseException extends Exception implements HttpExceptionInterface
{
    public $status;

    public $body;

    private $headers;

    /**
     * @param Response $response
     */
    public function __construct(Response $response, $note = null)
    {
        $this->status = $response->getStatusCode();
        $this->body = $response->getBody()->getContents();
        $this->headers = $response->getHeaders();
        if ($note) {
            $this->body = $note . "\n" . $this->body;
        }
        $body = Str::limit($this->body, 100);
        parent::__construct(__("Unexpected response (status=:status)\n:body", [
            'status' => $this->status,
            'body' => $body,
        ]), 0);
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
