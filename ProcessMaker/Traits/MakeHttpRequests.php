<?php

namespace ProcessMaker\Traits;

use Mustache_Engine;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Arr;

trait MakeHttpRequests
{
    /**
     * Send a HTTP request based on the datasource, configuration
     * and the process request data.
     *
     * @param array $data
     * @param array $config
     *
     * @return void
     */
    public function request(array $data = [], array $config = [])
    {
        $mustache = new Mustache_Engine();
        $endpoint = $this->endpoints[$config['endpoint']];
        $method = $mustache->render($endpoint->method, $data);
        $url = $mustache->render($endpoint->url, $data);
        $headers = json_decode($mustache->render($endpoint->headers, $data), true);
        $body = $mustache->render($endpoint->body, $data);

        return $this->response($this->call($method, $url, $headers, $body), $data, $config, $mustache);
    }

    /**
     * Prepare the response, using the mapping configuration
     *
     * @param Response $response
     * @param array $data
     * @param array $config
     * @param Mustache_Engine $mustache
     *
     * @return array
     */
    private function response($response, array $data = [], array $config = [], Mustache_Engine $mustache)
    {
        $status = $response->getStatusCode();
        switch (true) {
            case $status == 200:
                $return = json_decode($response->getBody()->getContents(), true);
            break;
            case $status > 200 && $status < 300:
                $return = [];
            break;
            default:
                throw new Exception("Status code: $status\n" . $response->getBody()->getContents());
        }
        $mapped = [];
        if (isset($config['dataMapping'])) {
            foreach ($config['dataMapping'] as $map) {
                $value = $mustache->render($map['value'], array_merge($data, $return));
                Arr::set($mapped, $map['key'], $value);
            }
        }
        return $mapped;
    }

    /**
     * Call an HTTP request
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param string $body
     *
     * @return Response
     */
    private function call($method, $url, array $headers, $body)
    {
        $client = new Client([]);
        $request = new Request($method, $url, $headers, $body);
        return $client->send($request);
    }
}
