<?php

namespace ProcessMaker\Exception;

use Exception;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Str;

/**
 * Thrown if an expression failed to be parsed
 *
 * @package ProcessMaker\Exceptions
 */

class DataSourceResponseException extends Exception
{
    public $status;
    public $body;

    /**
     * @param Response $response
     */
    public function __construct(Response $response)
    {
        $this->status = $response->getStatusCode();
        $this->body = $response->getBody()->getContents();
        parent::__construct(__("Unexpected response (status=:status)\n:body", [
            'status' => $this->status,
            'body' => Str::limit($this->body, 100),
        ]), 0);
    }
}
