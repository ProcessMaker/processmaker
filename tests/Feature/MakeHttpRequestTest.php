<?php

namespace Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use ProcessMaker\Traits\MakeHttpRequests as MakeHttpRequests;
use Tests\TestCase;

class MakeHttpRequestTest extends TestCase
{
    // Helper function to call private methods in tests
    private function callMethod($obj, $name, array $args)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }

    /**
     * Tests the prepareRequestWithOutboundConfig function
     */
    public function testRequestConstruction()
    {
        // Prepare the object that will use the trait
        $testStub = $this->getObjectForTrait(MakeHttpRequests::class);
        $testStub->endpoints = json_decode('{"create":{"url":"https://jsonplaceholder.typicode.com/users/{{userIdParam}}","body":"{\n    \"name\":\"{{nameParam}}\",\n    \"age\":\"{{ageParam}}\"\n}","view":false,"method":"PUT","params":[{"id":0,"key":"queryStringParam","value":null,"required":false}],"headers":[{"id":0,"key":"headerParam","value":null,"required":false}],"purpose":"create","updated":"2022-05-30 12:38:48","testData":"{\n    \"nameParam\":\"Dante\",\n    \"ageParam\": 12,\n    \"userIdParam\": 4\n}","body_type":"json","outboundConfig":[]}}', true);
        $testStub->credentials = ['verify_certificate' => true];
        $testStub->authtype = 'NONE';

        // This is the configuration that is created when configuring a connector in modeler
        $endpointConfig = [
            'dataSource' => 1,
            'endpoint' => 'create',
            'dataMapping' => [
                ['value' => 'id', 'key' => 'userId', 'format' => 'dotNotation'],
            ],
            'outboundConfig' => [
                ['value' => '{{nameValue}}', 'type' => 'BODY', 'key' => 'nameParam', 'format' => 'mustache'],
                ['value' => '{{ageValue}}', 'type' => 'BODY', 'key' => 'ageParam', 'format' => 'mustache'],
                ['value' => '{{queryStringValue}}', 'type' => 'PARAM', 'key' => 'queryStringParam', 'format' => 'mustache'],
                ['value' => '{{userIdValue}}', 'type' => 'PARAM', 'key' => 'userIdParam', 'format' => 'mustache'],
                ['value' => '{{headerValue}}', 'type' => 'HEADER', 'key' => 'headerParam', 'format' => 'mustache'],
            ],
        ];

        $requestData = [
            'nameValue' => 'testName',
            'ageValue'=> 'testAge',
            'headerValue' => 'testHeader',
            'queryStringValue' => 'testQueryString',
            'userIdValue' => 11,
        ];

        $request = $this->callMethod($testStub,
            'prepareRequestWithOutboundConfig',
            [$requestData, &$endpointConfig]);
        $this->assertNotNull($request);
        [$method, $url, $headers, $body, $bodyType] = array_values($request);

        // Verify all the request data parts
        $this->assertEquals('PUT', $method);
        $this->assertEquals('https://jsonplaceholder.typicode.com/users/11?queryStringParam=testQueryString', $url);
        $this->assertEquals(['Accept'=>'application/json', 'headerParam' => 'testHeader'], $headers);
        $this->assertEquals(json_decode('{"name": "testName", "age": "testAge"}'), json_decode($body));
        $this->assertEquals('json', $bodyType);
    }

    /**
     * Tests the prepareRequestWithOutboundConfig function with no standard parameters
     * (See comments in code for details)
     */
    public function testRequestConstructionWithoutCommonParams()
    {
        // Prepare the object that will use the trait
        $testStub = $this->getObjectForTrait(MakeHttpRequests::class);
        // Parameter endpoint url without protocol (without https://, etc.)
        $testStub->endpoints = json_decode('{"create":{"url":"/users/{{userIdParam}}?queryStringParam2={{queryStringValue2}}","body":"{\n    \"name\":\"{{nameParam}}\",\n    \"age\":\"{{ageParam}}\"\n}","view":false,"method":"PUT","params":[{"id":0,"key":"queryStringParam","value":null,"required":false}],"headers":[{"id":0,"key":"headerParam","value":null,"required":false}],"purpose":"create","updated":"2022-05-30 12:38:48","testData":"{\n    \"nameParam\":\"Dante\",\n    \"ageParam\": 12,\n    \"userIdParam\": 4\n}","body_type":"json","outboundConfig":[]}}', true);
        $testStub->credentials = ['verify_certificate' => true];
        $testStub->authtype = 'NONE';

        // This is the configuration that is created when configuring a connector in modeler
        $endpointConfig = [
            'dataSource' => 1,
            'endpoint' => 'create',
            'dataMapping' => [
                ['value' => 'id', 'key' => 'userId', 'format' => 'dotNotation'],
            ],
            'outboundConfig' => [
                ['value' => '{{nameValue}}', 'type' => 'BODY', 'key' => 'nameParam', 'format' => 'mustache'],
                // Body Parameter  without mustache expression (hardcoded 88)
                ['value' => '88', 'type' => 'BODY', 'key' => 'ageParam', 'format' => 'mustache'],
                ['value' => '{{queryStringValue}}', 'type' => 'PARAM', 'key' => 'queryStringParam', 'format' => 'mustache'],
                ['value' => '{{userIdValue}}', 'type' => 'PARAM', 'key' => 'userIdParam', 'format' => 'mustache'],
                ['value' => '{{headerValue}}', 'type' => 'HEADER', 'key' => 'headerParam', 'format' => 'mustache'],
            ],
        ];

        $requestData = [
            'nameValue' => 'testName',
            'ageValue'=> 'testAge',
            'headerValue' => 'testHeader',
            'queryStringValue' => 'testQueryString',
            'queryStringValue2' => 'testQueryString2',
            'userIdValue' => 11,
        ];

        $request = $this->callMethod($testStub,
            'prepareRequestWithOutboundConfig',
            [$requestData, &$endpointConfig]);
        $this->assertNotNull($request);
        [$method, $url, $headers, $body, $bodyType] = array_values($request);

        $this->assertEquals('PUT', $method);
        // we configured the url ($testStub->endpoints) without server so the current server must be added
        $this->assertEquals(url('/users/11?queryStringParam2=testQueryString2&queryStringParam=testQueryString'), $url);
        $this->assertEquals(['Accept'=>'application/json', 'headerParam' => 'testHeader'], $headers);
        //The body must contain the hardcoded attribute
        $this->assertEquals(json_decode('{"name": "testName", "age": 88}'), json_decode($body));
        $this->assertEquals('json', $bodyType);
    }

    /**
     * Verifies that different Guzzle Http Responses are mapped correctly calling the function responseWithHeaderData
     */
    public function testResponseMapping()
    {
        // Prepare the object that will use the trait
        $testStub = $this->getObjectForTrait(MakeHttpRequests::class);
        $testStub->endpoints = json_decode('{"create":{"url":"https://jsonplaceholder.typicode.com/users/{{userIdParam}}","body":"{\n    \"name\":\"{{nameParam}}\",\n    \"age\":\"{{ageParam}}\"\n}","view":false,"method":"PUT","params":[{"id":0,"key":"queryStringParam","value":null,"required":false}],"headers":[{"id":0,"key":"headerParam","value":null,"required":false}],"purpose":"create","updated":"2022-05-30 12:38:48","testData":"{\n    \"nameParam\":\"Dante\",\n    \"ageParam\": 12,\n    \"userIdParam\": 4\n}","body_type":"json","outboundConfig":[]}}', true);
        $testStub->credentials = ['verify_certificate' => true];
        $testStub->authtype = 'NONE';

        // This is the configuration that is created when configuring a connector in modeler
        $endpointConfig = [
            'dataSource' => 1,
            'endpoint' => 'create',
            'dataMapping' => [
                ['value' => 'id', 'key' => 'userId', 'format' => 'dotNotation'],
            ],
            'outboundConfig' => [
                ['value' => '{{nameValue}}', 'type' => 'BODY', 'key' => 'nameParam', 'format' => 'mustache'],
                ['value' => '88', 'type' => 'BODY', 'key' => 'ageParam', 'format' => 'mustache'],
                ['value' => '{{queryStringValue}}', 'type' => 'PARAM', 'key' => 'queryStringParam', 'format' => 'mustache'],
                ['value' => '{{userIdValue}}', 'type' => 'PARAM', 'key' => 'userIdParam', 'format' => 'mustache'],
                ['value' => '{{headerValue}}', 'type' => 'HEADER', 'key' => 'headerParam', 'format' => 'mustache'],
            ],
        ];

        $requestData = [
            'nameValue' => 'testName',
            'ageValue'=> 'testAge',
            'headerValue' => 'testHeader',
            'queryStringValue' => 'testQueryString',
            'queryStringValue2' => 'testQueryString2',
            'userIdValue' => 11,
        ];

        // Verify that the endpoint maps an attribute
        $stream = \GuzzleHttp\Psr7\Utils::streamFor('{"id" : "11", "name": "testName"}');
        $response = new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], $stream);
        $mapped = $this->callMethod($testStub,
            'responseWithHeaderData',
            [$response, $requestData, $endpointConfig]);
        $this->assertEquals(['userId' => 11], $mapped);

        // Verify that the endpoint maps all the response when value is empty (in connector config)
        $endpointConfig['dataMapping'] = [
            //value is empty so all the response should be mapped
            ['value' => '', 'key' => 'allData', 'format' => 'dotNotation'],
        ];
        $stream = \GuzzleHttp\Psr7\Utils::streamFor('{"id" : "11", "name": "testName"}');
        $response = new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], $stream);
        $mapped = $this->callMethod($testStub,
            'responseWithHeaderData',
            [$response, $requestData, $endpointConfig]);
        $this->assertEquals(['allData' =>['id' => 11, 'name' => 'testName']], $mapped);

        // Verify that the endpoint maps substructures
        $endpointConfig['dataMapping'] = [
            ['value' => 'data.user', 'key' => 'user', 'format' => 'dotNotation'],
            ['value' => 'data.code', 'key' => 'responseCode', 'format' => 'dotNotation'],
        ];
        $stream = \GuzzleHttp\Psr7\Utils::streamFor('{"data": {"user": {"id" : "11", "name": "testName"}, "code":99}}');
        $response = new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], $stream);
        $mapped = $this->callMethod($testStub,
            'responseWithHeaderData',
            [$response, $requestData, $endpointConfig]);
        $this->assertEquals(['user' =>['id' => 11, 'name' => 'testName'], 'responseCode' => 99], $mapped);
    }

    public function testCallRest()
    {
        // Prepare the object that will use the trait
        $testStub = $this->getObjectForTrait(MakeHttpRequests::class);
        $testStub->endpoints = json_decode('{"create":{"url":"https://fake.server.com/users/{{userIdParam}}","body":"{\n    \"name\":\"{{nameParam}}\",\n    \"age\":\"{{ageParam}}\"\n}","view":false,"method":"PUT","params":[{"id":0,"key":"queryStringParam","value":null,"required":false}],"headers":[{"id":0,"key":"headerParam","value":null,"required":false}],"purpose":"create","updated":"2022-05-30 12:38:48","testData":"{\n    \"nameParam\":\"Dante\",\n    \"ageParam\": 12,\n    \"userIdParam\": 4\n}","body_type":"json","outboundConfig":[]}}', true);
        $testStub->authtype = 'BASIC';
        $testStub->debug_mode = false;
        $testStub->credentials = ['verify_certificate' => false, 'username' => 'test', 'password' => 'test'];

        // This is the configuration that is created when configuring a connector in modeler
        $endpointConfig = [
            'dataSource' => 1,
            'endpoint' => 'create',
            'dataMapping' => [
                ['value' => 'id', 'key' => 'userId', 'format' => 'dotNotation'],
            ],
            'outboundConfig' => [
                ['value' => '{{nameValue}}', 'type' => 'BODY', 'key' => 'nameParam', 'format' => 'mustache'],
                ['value' => '{{ageValue}}', 'type' => 'BODY', 'key' => 'ageParam', 'format' => 'mustache'],
                ['value' => '{{queryStringValue}}', 'type' => 'PARAM', 'key' => 'queryStringParam', 'format' => 'mustache'],
                ['value' => '{{userIdValue}}', 'type' => 'PARAM', 'key' => 'userIdParam', 'format' => 'mustache'],
                ['value' => '{{headerValue}}', 'type' => 'HEADER', 'key' => 'headerParam', 'format' => 'mustache'],
            ],
        ];

        $requestData = [
            'nameValue' => 'testName',
            'ageValue'=> 'testAge',
            'headerValue' => 'testHeader',
            'queryStringValue' => 'testQueryString',
            'userIdValue' => 11,
        ];

        $request = $this->callMethod($testStub,
            'prepareRequestWithOutboundConfig',
            [$requestData, &$endpointConfig]);

        [$method, $url, $headers, $body, $bodyType] = array_values($request);

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"id": 1}'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $testStub->client = new Client(['handler' => $handlerStack]);

        $response = $this->callMethod($testStub, 'call', $request);

        $body = $response->getBody()->getContents();

        $this->assertEquals(json_decode('{"id": 1}'), json_decode($body));
    }

    public function testRequestCall()
    {
        // Prepare the object that will use the trait
        $testStub = $this->getObjectForTrait(MakeHttpRequests::class);
        $testStub->endpoints = json_decode('{"create":{"url":"https://fake.server.com/users/{{userIdParam}}","body":"{\n    \"name\":\"{{nameParam}}\",\n    \"age\":\"{{ageParam}}\"\n}","view":false,"method":"PUT","params":[{"id":0,"key":"queryStringParam","value":null,"required":false}],"headers":[{"id":0,"key":"headerParam","value":null,"required":false}],"purpose":"create","updated":"2022-05-30 12:38:48","testData":"{\n    \"nameParam\":\"Dante\",\n    \"ageParam\": 12,\n    \"userIdParam\": 4\n}","body_type":"json","outboundConfig":[]}}', true);
        $testStub->authtype = 'BASIC';
        $testStub->credentials = ['verify_certificate' => false, 'username' => 'test', 'password' => 'test'];
        $testStub->debug_mode = false;

        // This is the configuration that is created when configuring a connector in modeler
        $connectorConfig = [
            'dataSource' => 1,
            'endpoint' => 'create',
            'dataMapping' => [
                ['value' => 'remoteId', 'key' => 'pmRequestId', 'format' => 'dotNotation'],
            ],
            'outboundConfig' => [
                ['value' => '{{reqNameValue}}', 'type' => 'BODY', 'key' => 'nameParam', 'format' => 'mustache'],
                ['value' => '{{reqAgeValue}}', 'type' => 'BODY', 'key' => 'ageParam', 'format' => 'mustache'],
                ['value' => '{{reqQueryStringValue}}', 'type' => 'PARAM', 'key' => 'queryStringParam', 'format' => 'mustache'],
                ['value' => '{{reqUserIdValue}}', 'type' => 'PARAM', 'key' => 'userIdParam', 'format' => 'mustache'],
                ['value' => '{{reqHeaderValue}}', 'type' => 'HEADER', 'key' => 'headerParam', 'format' => 'mustache'],
            ],
        ];

        $requestData = [
            'reqNameValue' => 'testName',
            'reqAgeValue'=> 'testAge',
            'reqHeaderValue' => 'testHeader',
            'reqQueryStringValue' => 'testQueryString',
            'reqUserIdValue' => 11,
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"remoteId": 11}'),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $testStub->client = new Client(['handler' => $handlerStack]);

        $result = $testStub->request($requestData, $connectorConfig);
        $this->assertEquals(['pmRequestId' => 11], $result);

        // Using BEARER Authentication
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"remoteId": 11}'),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $testStub->client = new Client(['handler' => $handlerStack]);
        $testStub->authtype = 'OAUTH2_BEARER';
        $testStub->credentials = ['verify_certificate' => false, 'token' => 'test'];
        $result = $testStub->request($requestData, $connectorConfig);
        $this->assertEquals(['pmRequestId' => 11], $result);

        // Using OAUTH2_PASSWORD Authentication
        $mock = new MockHandler([
            // Response for the oauth code:
            new Response(200, ['Content-Type' => 'application/json'], '{"access_token": "fake_token"}'),
            // Response for the fake endpoint:oauth
            new Response(200, ['Content-Type' => 'application/json'], '{"remoteId": 11}'),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $testStub->client = new Client(['handler' => $handlerStack]);
        $testStub->authtype = 'OAUTH2_PASSWORD';
        $testStub->credentials = [
            'verify_certificate' => false,
            'token' => 'test',
            'username' => 'testUser',
            'password' => 'testPassword',
            'grant_type' => 'password',
            'client_id' => 'testCliendId',
            'client_secret' => 'testSecret',
            'url' => 'http://www.test.com',
        ];
        $result = $testStub->request($requestData, $connectorConfig);
        $this->assertEquals(['pmRequestId' => 11], $result);
    }

    public function testRequestCallsWhenEndPointReturnErrors()
    {
        // Prepare the object that will use the trait
        $testStub = $this->getObjectForTrait(MakeHttpRequests::class);
        $testStub->endpoints = json_decode('{"create":{"url":"https://fake.server.com/users/{{userIdParam}}","body":"{\n    \"name\":\"{{nameParam}}\",\n    \"age\":\"{{ageParam}}\"\n}","view":false,"method":"PUT","params":[{"id":0,"key":"queryStringParam","value":null,"required":false}],"headers":[{"id":0,"key":"headerParam","value":null,"required":false}],"purpose":"create","updated":"2022-05-30 12:38:48","testData":"{\n    \"nameParam\":\"Dante\",\n    \"ageParam\": 12,\n    \"userIdParam\": 4\n}","body_type":"json","outboundConfig":[]}}', true);
        $testStub->authtype = 'NONE';
        $testStub->debug_mode = false;
        // This is the configuration that is created when configuring a connector in modeler
        $endpointConfig = [
            'dataSource' => 1,
            'endpoint' => 'create',
            'dataMapping' => [
                ['value' => 'id', 'key' => 'userId', 'format' => 'dotNotation'],
            ],
            'outboundConfig' => [
                ['value' => '{{nameValue}}', 'type' => 'BODY', 'key' => 'nameParam', 'format' => 'mustache'],
                ['value' => '{{ageValue}}', 'type' => 'BODY', 'key' => 'ageParam', 'format' => 'mustache'],
                ['value' => '{{queryStringValue}}', 'type' => 'PARAM', 'key' => 'queryStringParam', 'format' => 'mustache'],
                ['value' => '{{userIdValue}}', 'type' => 'PARAM', 'key' => 'userIdParam', 'format' => 'mustache'],
                ['value' => '{{headerValue}}', 'type' => 'HEADER', 'key' => 'headerParam', 'format' => 'mustache'],
            ],
        ];

        $requestData = [
            'nameValue' => 'testName',
            'ageValue'=> 'testAge',
            'headerValue' => 'testHeader',
            'queryStringValue' => 'testQueryString',
            'userIdValue' => 11,
        ];
        $testStub->credentials = ['verify_certificate' => false];

        // With no json body content
        $mock = new MockHandler([
            new Response(200, [], 'No Json response'),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $testStub->client = new Client(['handler' => $handlerStack]);
        $result = $testStub->request($requestData, $endpointConfig);
        $this->assertEquals(200, $result['status']);
        $this->assertEquals('No Json response', $result['response']);

        // Response with status >200 and <300
        $mock = new MockHandler([
            new Response(201, [], '{"id": 20}'),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $testStub->client = new Client(['handler' => $handlerStack]);
        $result = $testStub->request($requestData, $endpointConfig);
        $this->assertEquals(20, $result['userId']);

        //Response with statuss different to 2xx
        $mock = new MockHandler([
            new Response(302, [], null),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $testStub->client = new Client(['handler' => $handlerStack]);
        $result = $testStub->request($requestData, $endpointConfig);
        $this->assertEquals(302, $result['status']);
    }
}
