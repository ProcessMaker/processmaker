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

    public function call($config, ...$requestParts)
    {
        [$method, $url, $headers, $body, $bodyType] = $requestParts;

        $client = $this->client;
        //$client = new Client(['verify' => $config['verifySsl']]);
        $options = [];
        if ($config['bodyType'] === 'form-data') {
            $options['form_params'] = json_decode($body, true);
        }

        $request = new Request($method, $url, $headers, $body);
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
//**        return $content;
    }
}