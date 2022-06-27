<?php

namespace Tests\Feature\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use ProcessMaker\Contracts\SoapClientInterface;
use ProcessMaker\Managers\WebServiceSoapServiceCaller;
use Tests\TestCase;

class WebServiceRequestTest extends TestCase
{
    use WithFaker;

    /**
     *
     * @var WebServiceSoapServiceCaller
     */
    private $manager;

    protected function setUpManager(): void
    {
        $this->app = $this->createApplication();
    }

    public function testBuildUserPasswordAuthRequest()
    {
        // Mock SoapClientInterface
        $mock = Mockery::mock(SoapClientInterface::class, function ($mock) {
            $mock->shouldReceive('callMethod')
                ->with("Ping", [
                    "body" => [
                        "PingRq" => "success"
                    ]
                ])
                ->andReturn((object)[
                    "PingRs" => (object)[
                        "_" => "success"
                    ]
                ]);
        });
        $this->app->bind(SoapClientInterface::class, function () use ($mock) {
            return $mock;
        });
        $mockDataSource = Mockery::mock(Model::class, function ($mock) {
            $mock->shouldReceive('getAttribute')->with('credentials')->andReturn([
                "wsdl" => "1/TPG_Customer.wsdl",
                "user" => "test",
                "password" => "password",
                "location" => "https://jxtest.processmaker.local/jxchange/2008/ServiceGateway/Customer.svc",
                "authentication_method" => "password"
            ]);
            $mock->shouldReceive('toArray')->andReturn([
                'id' => 1,
                'endpoints' => [
                    "Ping" => [
                        "method" => "SOAP",
                        "operation" => [
                            "text" => "Ping",
                            "value" => "Ping"
                        ],
                        "params" => [
                            [
                                "key" => "PingRq",
                                "type" => "string",
                                "required" => false
                            ]
                        ]
                    ]
                ],
                "debug_mode" => false
            ]);
        });
        $serviceTask = app('WebServiceRequest', ['dataSource' => $mockDataSource]);
        $response = $serviceTask->execute(
            [
                'PingRq' => 'success',
            ],
            [
                "dataSource" => 1,
                "endpoint" => "Ping",
                "dataMapping" => [
                    [
                        "value" => "",
                        "key" => "response",
                        "format" => "dotNotation"
                    ]
                ],
                "outboundConfig" => [],
            ]
        );
        $this->assertEquals([
            'response' => [
                "PingRs" => [
                    "_" => "success"
                ]
            ]
        ], $response);
    }
}
