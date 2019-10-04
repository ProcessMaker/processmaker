<?php

namespace ProcessMaker\Traits;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Mustache_Engine;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Arr;
use ProcessMaker\Exception\HttpResponseException;
use Psr\Http\Message\ResponseInterface;

trait MakeHttpRequests
{
    private $authTypes = [
        'BASIC' => 'basicAuthorization',
        'BEARER' => 'bearerAuthorization',
        'PASSWORD' => 'passwordAuthorization',
    ];

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
    public function request(array $data = [], array $config = [])
    {
        $mustache = new Mustache_Engine();
        $endpoint = $this->endpoints[$config['endpoint']];
        $method = $mustache->render($endpoint['method'], $data);
        $url = $mustache->render($endpoint['url'], $data);
        // Datasource works with json responses
        $headers = ['Accept' => 'application/json'];
        if (isset($endpoint['headers']) && is_array($endpoint['headers'])) {
            foreach ($endpoint['headers'] as $header) {
                $headers[$mustache->render($header['key'], $data)] = $mustache->render($header['value'], $data);
            }
        }
        $body = $mustache->render($endpoint['body'], $data);
        $bodyType = $mustache->render($endpoint['body_type'], $data);
        $request = [$method, $url, $headers, $body, $bodyType];
        $request = $this->addAuthorizationHeaders(...$request);
        try {
            return $this->response($this->call(...$request), $data, $config, $mustache);
        } catch (ClientException $exception) {
            throw new HttpResponseException($exception->getResponse());
        }
    }

    /**
     * Add authorization parameters
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
     * @param $bodyType
     *
     * @return array
     */
    private function basicAuthorization($method, $url, $headers, $body, $bodyType)
    {
        if (isset($this->credentials) && is_array($this->credentials)) {
            $headers['Authorization'] = 'Basic ' . base64_encode($this->credentials['username'] . ':' . $this->credentials['password']);
        }
        return [$method, $url, $headers, $body, $bodyType];
    }

    /**
     * Add bearer authorization to header
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param string $body
     * @param $bodyType
     *
     * @return array
     */
    private function bearerAuthorization($method, $url, $headers, $body, $bodyType)
    {
        if (isset($this->credentials) && is_array($this->credentials)) {
            $headers['Authorization'] = 'Bearer ' . $this->credentials['token'];
        }
        return [$method, $url, $headers, $body, $bodyType];
    }

    /**
     * Get token with credentials
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param string $body
     * @param $bodyType
     *
     * @return array
     */
    private function passwordAuthorization($method, $url, $headers, $body, $bodyType)
    {
        if (isset($this->credentials) && is_array($this->credentials)) {
            //todo enable mustache
            $config = [
                'username' => $this->credentials['username'],
                'password' => $this->credentials['password'],
                'grant_type' => 'password',
                'client_id' => $this->credentials['client_id'],
                'client_secret' => $this->credentials['client_secret'],
            ];

            $token = $this->response($this->call('POST', $this->credentials['url'], $headers, json_encode($config), 'form-data'), [], ['dataMapping' => []], new Mustache_Engine());
            $headers['Authorization'] = 'Bearer ' . $token['response']['access_token'];
        }
        return [$method, $url, $headers, $body, $bodyType];
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
     * @throws HttpResponseException
     */
    private function response($response, array $data = [], array $config = [], Mustache_Engine $mustache)
    {
        $status = $response->getStatusCode();
        switch (true) {
            case $status == 200:
                $content = json_decode($response->getBody()->getContents(), true);
                break;
            case $status > 200 && $status < 300:
                $content = [];
                break;
            default:
                throw new HttpResponseException($response);
        }
        $mapped = [];
        !is_array($content) ?: $merged = array_merge($data, $content);
        $mapped['status'] = $status;
        $mapped['response'] = $content;

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
     * @param $body
     * @param string $bodyType
     *
     * @return mixed|ResponseInterface
     *
     * @throws GuzzleException
     */
    private function call($method, $url, array $headers, $body, $bodyType)
    {
        $client = new Client([]);
        $options = [];
        if ($bodyType === 'form-data') {
            $options['form_params'] = json_decode($body, true);
        }
        $request = new Request($method, $url, $headers, $body);
        return $client->send($request, $options);
    }
}
