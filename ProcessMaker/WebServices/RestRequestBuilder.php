<?php

namespace ProcessMaker\WebServices;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mustache_Engine;
use ProcessMaker\Exception\HttpInvalidArgumentException;
use ProcessMaker\Exception\HttpResponseException;
use ProcessMaker\Helpers\DataTypeHelper;

class RestRequestBuilder implements Contracts\WebServiceRequestBuilderInterface
{

    private $authTypes = [
        'BASIC' => 'basicAuthorization',
        'OAUTH2_BEARER' => 'bearerAuthorization',
        'OAUTH2_PASSWORD' => 'passwordAuthorization',
    ];

    private $config;

    private $client;

    /**
     * @param $client
     */
    public function __construct($client)
    {
        $this->client = $client;
    }


    public function build($config, $requestData)
    {
//**        $outboundConfig = $config['outboundConfig'] ?? [];
//**        $endpoint = $this->endpoints[$config['endpoint']];
//**        $this->verifySsl = array_key_exists('verify_certificate', $this->credentials)
//**            ? $this->credentials['verify_certificate']
//**            : true;
//**
//**       // Prepare URL
//**       $params = $this->prepareData($requestData, $outboundConfig, 'PARAM');
//**       $method = $this->evalMustache($endpoint['method'], $requestData);

        //TODO ver si se puede mover para evitar la var. global config
        $this->config = $config;

        $url = $this->addQueryStringsParamsToUrl($config['endpoint'], $config, $requestData, $config['params']);
        $method = $config['method'];
        $headers = $config['headers'];
        $body = $config['body'];
        $bodyType = $config['bodyType'];

        $request = [$method, $url, $headers, $body, $bodyType];
        $request = $this->addAuthorizationHeaders($config, ...$request);
        return $request;
    }

    /**
     * @param string $url
     * @param $endpoint
     * @param array $config
     * @param array $data
     * @param array $params
     *
     * @return string
     */
    private function addQueryStringsParamsToUrl($endpoint, array $config, array $data, array $params = [])
    {
        // Note that item['key'] corresponds to an endpoint property (in the header, querystring, etc.)
        //           item['value'] corresponds to a PM request variable or mustache expression
        //           item['type'] location of the property defined in 'key'. It can be BODY, PARAM (in the query string), HEADER

        $url = $endpoint['url'];
        // If url does not include the protocol and server name complete it with the local server
        if (substr($url, 0,1) === '/') {
            $url = url($url);
        }

        // Evaluate mustache expressions in URL
        $url = ExpressionEvaluator::evaluate('mustache', $url, array_merge($data, $params));
        // Add params from datasource configuration
        $parsedUrl = $this->parseUrl($url);
        $query = [];
        parse_str($parsedUrl['query'] ?? '', $query);
        if (array_key_exists('params', $endpoint)) {
            foreach ($endpoint['params'] as $param) {
                $key = ExpressionEvaluator::evaluate('mustache', $param['key'], $data);
                // Get value from outbound configuration, if not defined get the default value
                $value = $params[$key] ?? ExpressionEvaluator::evaluate('mustache', $param['value'], $data);
                if ($value !== '' || $param['required']) {
                    $query[$key] = $value;
                }
            }
        } else {
            foreach ($params as $key => $value) {
                if ($value !== '') {
                    $query[$key] = $value;
                }
            }
        }

        // If exists a query string in the call, add/replace it into the URL
        if (array_key_exists('queryString', $config)) {
            parse_str($config['queryString'], $fromSelectListPmql);
            $query = array_merge($query, $fromSelectListPmql);
        }

        $parsedUrl['query'] = http_build_query($query);
        $url = $this->unparseUrl($parsedUrl);
        return $url;
    }

    /**
     * Multibyte parse_url
     *
     * @param string $url
     * @return array
     */
    public function parseUrl($url)
    {
        $enc_url = preg_replace_callback(
            '%[^:/@?&=#]+%usD',
            function ($matches) {
                return urlencode($matches[0]);
            },
            $url
        );
        $parts = parse_url($enc_url);
        if ($parts === false) {
            throw new HttpInvalidArgumentException('Malformed URL: ' . $url);
        }
        foreach ($parts as $name => $value) {
            $parts[$name] = urldecode($value);
        }
        return $parts;
    }

    /**
     * Unparse url array
     *
     * @param array $parsed_url
     * @return string
     */
    private function unparseUrl(array $parsed_url)
    {
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = !empty($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /**
     * Add authorization parameters
     *
     * @param array ...$request
     *
     * @return array
     */
    private function addAuthorizationHeaders($config, ...$request)
    {
        if (isset($this->authTypes[$config['authtype']])) {
            $callable = [$this, $this->authTypes[$config['authtype']]];
            return call_user_func_array($callable, $request);
        }
        return $request;
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
        if (isset($this->config['credentials']) && is_array($this->config['credentials'])) {
            $headers['Authorization'] = 'Basic ' . base64_encode($this->config['credentials']['username'] . ':' . $this->config['credentials']['password']);
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
        if (isset($this->config['credentials']) && is_array($this->config['credentials'])) {
            $headers['Authorization'] = 'Bearer ' . $this->config['credentials']['token'];
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
        if (isset($this->config['credentials']) && is_array($this->config['credentials'])) {
            //todo enable mustache
            $config = [
                'username' => $this->config['credentials']['username'],
                'password' => $this->config['credentials']['password'],
                'grant_type' => 'password',
                'client_id' => $this->config['credentials']['client_id'],
                'client_secret' => $this->config['credentials']['client_secret'],
            ];

            $token = $this->response($this->call('POST', $this->config['credentials']['url'], ['Accept' => 'application/json'], json_encode($config), 'form-data'), [], ['dataMapping' => []], new Mustache_Engine());
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
    private function response($response, array $data = [], array $config = [])
    {
        $status = $response->getStatusCode();
        $bodyContent = $response->getBody()->getContents();
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
        $mapped = [];
        !is_array($content) ?: $merged = array_merge($data, $content);
        $mapped['status'] = $status;
        $mapped['response'] = $content;

        if (isset($config['dataMapping'])) {
            foreach ($config['dataMapping'] as $map) {
                if ($map['value']) {
                    $value = Arr::get($merged, $map['value'], '');
                } else {
                    $value = $content;
                }
                Arr::set($mapped, $map['key'], $value);
            }
        }
        return $mapped;
    }

    private function call($method, $url, array $headers, $body, $bodyType)
    {
        $client = $this->client ?? new Client(['verify' => $this->config['verifySsl']]);

        $options = [];
        if ($bodyType === 'form-data') {
            $options['form_params'] = json_decode($body, true);
        }
        $request = new Request($method, $url, $headers, $body);
        return $client->send($request, $options);
    }
}