<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class DataSourceService
{
    public function main($data, $config)
    {
        $newData = $this->api('POST', '/requests/' . $data['_request']['id'] . '/datasources/' . $config['dataSource'], ['config' => $config]);
        return $newData;
    }

    private function call($method, $url, $headers, $body)
    {
        $client = new Client([]);
        $request = new Request($method, $url, $headers, $body);
        return $client->send($request);
    }

    private function api($method, $route, $body = null)
    {
        $headers = $this->getApiHeaders();
        $response = $this->call($method, getenv('HOST_URL') . '/api/1.0' . $route, $headers, isset($body) ? json_encode($body) : '');
        $content = $response->getBody()->getContents();
        return json_decode($content);
    }

    private function getApiHeaders()
    {
        $token = getenv('API_TOKEN');
        return [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ];
    }
}

return (new DataSourceService)->main($data, $config);
