<?php

namespace Tests\Feature\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use ProcessMaker\WebServices\Contracts\SoapClientInterface;
use ProcessMaker\WebServices\SoapConfigBuilder;
use ProcessMaker\WebServices\SoapRequestBuilder;
use ProcessMaker\WebServices\SoapResponseMapper;
use ProcessMaker\WebServices\SoapServiceCaller;
use ProcessMaker\WebServices\WebServiceRequest;
use ProcessMaker\WebServices\WebServiceRequestFactory;
use Tests\TestCase;

class WebServiceSoapTest extends TestCase
{
    use WithFaker;

    /**
     *
     * @var SoapServiceCaller
     */
    private $manager;

    protected function setUpManager(): void
    {
        $this->app = $this->createApplication();
    }

    public function testExecution()
    {
        $mockedDataSource = Mockery::mock(Model::class, function ($mock) {
            $mock->shouldReceive('getAttribute')->with('credentials')->andReturn([
                "wsdl"=>"1/TPG_Customer.wsdl",
                "username"=>"test",
                "user"=>"test",
                "password"=>"password",
                "location"=>"https://jxtest.processmaker.local/jxchange/2008/ServiceGateway/Customer.svc",
                "authentication_method"=>"password"
            ]);
            $mock->shouldReceive('toArray')->andReturn([
                'id' => 1,
                'endpoints' => [
                    "Ping" => [
                        "method"=>"SOAP",
                        "operation" => ["value" => "Ping", "text" => "Ping"],
                        "params" => [
                            [
                                "key" => "PingRq",
                                "type" => "string",
                                "required" => false
                            ]
                        ]
                    ]
                ],
                "debug_mode"=>false
            ]);
        });

        $this->app = $this->createApplication();

        // Mock SoapClientInterface
        $mock = Mockery::mock(SoapClientInterface::class, function ($mock) {
            $mock->shouldReceive('callMethod')
                ->with("Ping", [ "body" => [ "PingRq" => "success" ] ])
                ->andReturn((object)[ "PingRs" => (object)[ "_" => "success" ] ]);
        });

        $this->app->bind(SoapClientInterface::class, function () use ($mock) {
            return $mock;
        });

        $wsConfig =  new SoapConfigBuilder();
        $wsBuilder = new SoapRequestBuilder();
        $wsMapper = new SoapResponseMapper();
        $wsCaller = new SoapServiceCaller();
        //$wsRequest = new WebServiceRequest($wsConfig, $wsBuilder, $wsMapper, $wsCaller, $mockedDataSource);
        $wsRequest = (new WebServiceRequestFactory())->create('soap', $mockedDataSource);
        $requestData = ['PingRq' => 'success'];
        $connectorConfig = [ "dataSource" => 1, "endpoint" => "Ping", "dataMapping" => [ [ "value" => "", "key" => "response", "format" => "dotNotation" ] ], "outboundConfig" => []];
        $response = $wsRequest->execute($requestData, $connectorConfig);
        $this->assertEquals([ 'response' => [ "PingRs" => [ "_" => "success" ] ] ], $response);
    }
}
