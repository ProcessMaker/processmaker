<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
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
        $this->app = app();
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
                'login' => 'admin',
                'password' => 'password',
            ],
            'method' => 'test',
            'parameters' => [
                'param1' => 'value1',
                'param2' => 'value2',
            ],
        ];
        $response = $this->manager->call($request);
        $this->assertEquals('success', $response['response']);
    }
}
