<?php

namespace ProcessMaker\Traits;

use Mustache_Engine;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;

trait MakeHttpRequests
{
    private $authTypes = [
        'BASIC' => 'basicAuthorization',
        'BEARER' => 'bearerAuthorization',
    ];

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
        $method = $mustache->render($endpoint['method'], $data);
        $url = $mustache->render($endpoint['url'], $data);
        $headers = [];
        if (isset($endpoint['headers']) && is_array($endpoint['headers'])) {
            foreach ($endpoint['headers'] as $key => $value) {
                $headers[$mustache->render($key, $data)] = $mustache->render($value, $data);
            }
        }
        $body = $mustache->render($endpoint['body'], $data);
        $config = [$method, $url, $headers, $body];

        $config = $this->addAuthorizationHeaders(...$config);
        return $this->response($this->call(...$config), $data, $config, $mustache);
    }

    /**
     * Add authorization paramaters
     *
     * @param array ...$config
     *
     * @return array
     */
    private function addAuthorizationHeaders(...$config)
    {
        if (isset($this->authTypes[$this->authtype])) {
            $callable = [$this, $this->authTypes[$this->authtype]];
            return call_user_func_array($callable, $config);
        }
        return $config;
    }

    /**
     * Add basic authorization to header
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param string $body
     *
     * @return array
     */
    private function basicAuthorization($method, $url, $headers, $body)
    {
        $credentials = json_decode(Crypt::decryptString($this->credentials), true);
        $headers['Authorization'] = 'Basic ' . $credentials['username'] . ':' . $credentials['password'];
        return [$method, $url, $headers, $body];
    }

    /**
     * Add bearer authorization to header
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param string $body
     *
     * @return array
     */
    private function bearerAuthorization($method, $url, $headers, $body)
    {
        $credentials = json_decode(Crypt::decryptString($this->credentials), true);
        $headers['Authorization'] = 'Bearer ' . $credentials['token'];
        return [$method, $url, $headers, $body];
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
        \Log::info(json_encode($response->getBody()->getContents()));
        $merged = array_merge($data, $return);
        if (isset($config['dataMapping'])) {
            foreach ($config['dataMapping'] as $map) {
                //$value = $mustache->render($map['value'], $merged);
                $value = Arr::get($merged, $map['value'], '');
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
