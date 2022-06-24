<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Mockery\MockInterface;
use ProcessMaker\Contracts\SoapClientInterface;
use ProcessMaker\Managers\WebServiceSoapServiceCaller;
use Tests\TestCase;

class WebServiceSoapServiceCallerTest extends TestCase
{
    use WithFaker;

    /**
     *
     * @var WebServiceSoapServiceCaller
     */
    private $manager;

    protected function setUpManager(): void
    {
        $this->manager = new WebServiceSoapServiceCaller;
        $this->app = $this->createApplication();
    }

    public function testBuildUserPasswordAuthRequest()
    {
        // Mock SoapClientInterface
        $mock = Mockery::mock(SoapClientInterface::class, function ($mock) {
            $mock->shouldReceive('callMethod')->andReturn(['response' => 'success']);
        });
        $this->app->bind(SoapClientInterface::class, function () use ($mock) {
            return $mock;
        });
        $request = [
            'wsdl' => 'http://test.processmaker.net/soap/globalweather?WSDL',
            'options' => [
                'exceptions' => true,
                'trace' => true,
                'authentication_method' => 'password',
                'login' => 'admin',
                'password' => 'password',
            ],
            "operation" => [
                "text" => "test",
                "value" => "test"
            ],
            'parameters' => [
                'param1' => 'value1',
                'param2' => 'value2',
            ],
        ];
        $response = $this->manager->call($request);
        $this->assertEquals('success', $response['response']);
    }

    public function testSoapClientServicePort()
    {
        $mock = $this->partialMock(SoapClientInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('selectServicePort')->with('CustomerServiceSoap');
            $mock->shouldReceive('callMethod')->with('Ping', ['body'=>['PingRq' => 'success']])->once()->andReturn((object)['PingRs' => (object)["_" => 'success']]);
        });
        $this->app->bind(SoapClientInterface::class, function () use ($mock) {
            return $mock;
        });
        $wsdl = __DIR__ . '/../Fixtures/WithServicePort.wsdl';
        $request = [
            'wsdl' => $wsdl,
            'options' => [
                'exceptions' => true,
                'trace' => true,
                'authentication_method' => 'password',
                'login' => 'testing@jxtest.local',
                'password' => 'abcdef1234567890',
            ],
            'service_port' => 'CustomerServiceSoap',
            "operation" => [
                "text" => "Ping",
                "value" => "Ping"
            ],
            'parameters' => [
                'PingRq' => 'success',
            ],
        ];
        $response = $this->manager->call($request);
        $this->assertEquals('success', $response->PingRs->_);
    }
}
