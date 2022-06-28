<?php

namespace ProcessMaker\Traits;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use ProcessMaker\Exception\HttpResponseException;
use ProcessMaker\Helpers\DataTypeHelper;
use ProcessMaker\WebServices\RestResponseMapper;
use ProcessMaker\WebServices\RestRequestBuilder;
use ProcessMaker\WebServices\RestServiceCaller;
use ProcessMaker\WebServices\RestConfigBuilder;

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
            $response = $this->call($request);
            return $this->responseWithHeaderData($response, $requestData, $config);
        } catch (ClientException $exception) {
            throw new HttpResponseException($exception->getResponse());
        }
    }

    public function prepareRequestWithOutboundConfig($data, $config)
    {
        $configBuilder = new RestConfigBuilder();
        $requestBuilder = new RestRequestBuilder($this->client ?? null);
        $dsConfig = ['endpoints' => $this->endpoints, 'credentials' => $this->credentials, 'authtype' => $this->authtype];
        //TODO mover para que config no necesite una prop. privada
        $config = $configBuilder->build($config, $dsConfig, $data);
        $this->config = $config;
        //TODO como en el caso del dsConfig ver que no sea necesario el client
        $request = $requestBuilder->build($config, $data);
        return $request;
    }

    private function responseWithHeaderData($response, array $data = [], array $config = [])
    {
        if (is_array($response)) {
            $status = $response['status'];
            $bodyContent = $response['response'];
            $headers = $response['headers'] ?? [];
        }
        else {
            $status = $response->getStatusCode();
            $bodyContent = $response->getBody()->getContents();
            $headers = $response->getHeaders();
        }

        if (!DataTypeHelper::isJson($bodyContent)) {
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

        $mapper = new RestResponseMapper();
        $dsConfig = ['endpoints' => $this->endpoints, 'credentials' => $this->credentials];
        $responseData = compact('content', 'status', 'headers');
        $config['dataSourceInfo'] = $dsConfig;
        $mapped = $mapper->map($responseData, $config, $data);

        return $mapped;
    }

    private function call($request)
    {
        $client = $this->client ?? new Client(['verify' => $this->verifySsl]);
        $caller = new RestServiceCaller($client);
        return $caller->call($request);
    }
}
