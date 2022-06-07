<?php

namespace ProcessMaker\Traits;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Arr;
use Mustache_Engine;
use ProcessMaker\Exception\HttpInvalidArgumentException;
use ProcessMaker\Exception\HttpResponseException;
use ProcessMaker\Models\FormalExpression;
use ProcessMaker\WebServices\JsonResponseMapper;
use ProcessMaker\WebServices\RestRequestBuilder;
use ProcessMaker\WebServices\RestServiceCaller;
use ProcessMaker\WebServices\WebServiceConfigBuilder;
use Psr\Http\Message\ResponseInterface;

trait MakeHttpRequests
{
    private $config;
    /**
     * Send a HTTP request based on the datasource, configuration
     * and the process request data.
     *
     * @param array $data
     * @param array $config
     *
     * @return array
     *
     * @throws GuzzleException
     * @throws HttpResponseException
     */
    public function request(array $requestData = [], array $config = [])
    {
        try {
            $request = $this->prepareRequestWithOutboundConfig($requestData, $config);
            $response = $this->call($config, ...$request);
            return $this->responseWithHeaderData($response, $requestData, $config);
        } catch (ClientException $exception) {
            throw new HttpResponseException($exception->getResponse());
        }
    }

    public function prepareRequestWithOutboundConfig($data, $config)
    {
        $configBuilder = new WebServiceConfigBuilder();
        $requestBuilder = new RestRequestBuilder();
        $dsConfig = ['endpoints' => $this->endpoints, 'credentials' => $this->credentials, 'authtype' => $this->authtype];
        //TODO mover para que config no necesite una prop. privada
        $config = $configBuilder->build($config, $dsConfig, $data);
        $this->config = $config;
        //TODO como en el caso del dsConfig ver que no sea necesario el client
        $request = $requestBuilder->build($config, $data, $this->client ?? null);
        return $request;
    }

    private function responseWithHeaderData($response, array $data = [], array $config = [])
    {
        if (is_array($response)) {
            $status = $response['status'];
            $bodyContent = $response['response'];
            $responseHeaders = $response['headers'] ?? [];
        }
        else {
            $status = $response->getStatusCode();
            $bodyContent = $response->getBody()->getContents();
            $responseHeaders = $response->getHeaders();
        }

        if (!$this->isJson($bodyContent)) {
            return ["response" => $bodyContent, "status" => $status];
        }

        switch (true) {
            case $status == 200:
                $content = json_decode($bodyContent, true);
                break;
            case $status > 200 && $status < 300:
                $content = !empty($bodyContent) ? json_decode($bodyContent, true) : [];
                break;
            default:
                throw new HttpResponseException($response);
        }

        $mapper = new JsonResponseMapper();
        $dsConfig = ['endpoints' => $this->endpoints, 'credentials' => $this->credentials];
        $mapped = $mapper->map($content, $status, $responseHeaders, $config, $dsConfig, $data);

        return $mapped;
    }

    private function isJson($str) {
        json_decode($str);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    //private function call($method, $url, array $headers, $body, $bodyType)
    private function call($config, ...$request)
    {
        $client = $this->client ?? new Client(['verify' => $this->verifySsl]);
        $caller = new RestServiceCaller($client);
        return $caller->call($this->config, ...$request);
    }

}
