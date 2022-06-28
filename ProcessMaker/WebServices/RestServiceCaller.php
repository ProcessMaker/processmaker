<?php

namespace ProcessMaker\WebServices;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use ProcessMaker\Exception\HttpResponseException;
use ProcessMaker\Helpers\DataTypeHelper;
use ProcessMaker\WebServices\Contracts\WebServiceCallerInterface;

class RestServiceCaller implements WebServiceCallerInterface
{
    private $client;

    /**
     * @param $client
     */
    public function __construct($client)
    {
        $this->client = $client;
    }

    public function call($requestParts)
    {
        ['method' => $method, 'url' => $url, 'headers' => $headers, 'body' => $body, 'bodyType' => $bodyType, 'options' => $options] = $requestParts;

        $client = $this->client;

        $request = new Request($method, $url, $headers, $body, $options);
        $response = $client->send($request, $options);

        $status =  $response->getStatusCode();
        $bodyContent = $response->getBody()->getContents();
        if (!DataTypeHelper::isJson($bodyContent)) {
            return ["response" => $bodyContent, "status" => $status];
        }

        switch (true) {
            case $status == 200:
                $content = $bodyContent;
                break;
            case $status > 200 && $status < 300:
                $content = !empty($bodyContent) ? $bodyContent : [];
                break;
            default:
                throw new HttpResponseException($response);
        }

        return ['response' => $content, 'status' => $status, 'headers' => $response->getHeaders()];
    }
}