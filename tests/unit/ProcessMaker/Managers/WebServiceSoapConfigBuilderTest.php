<?php

namespace ProcessMaker\Managers;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebServiceSoapConfigBuilderTest extends TestCase
{

    /**
     *
     * @var WebServiceSoapConfigBuilder
     */
    private $manager;

    protected function setUpManager(): void
    {
        $this->manager = new WebServiceSoapConfigBuilder;
    }

    public function testBuildUserPasswordAuthRequest()
    {
        $serviceTaskConfig = [];
        $dataSourceConfig = [
            'credentials' => [
                'wsdl' => 'http://test.processmaker.net/soap/globalweather?WSDL',
                'authentication_method' => 'password',
                'username' => 'admin',
                'password' => 'password',
                'debug_mode' => true,
            ],
        ];
        $data = [];
        $config = $this->manager->build($serviceTaskConfig, $dataSourceConfig, $data);
        $this->assertEquals('http://test.processmaker.net/soap/globalweather?WSDL', $config['wsdl']);
        $this->assertEquals('password', $config['authentication_method']);
        $this->assertEquals('admin', $config['username']);
        $this->assertEquals('password', $config['password']);
        $this->assertEquals(true, $config['debug_mode']);
    }

    public function testBuildServiceTaskConfig()
    {
        $serviceTaskConfig = [
            "dataSource" => 1,
            "endpoint" => "list",
            "dataMapping" => [
                [
                    "value" => "PingRs._",
                    "key" => "response",
                    "format" => "dotNotation"
                ]
            ],
            "outboundConfig" => [
                [
                    "value" => "{{form_input_1}}",
                    "type" => "PARAM",
                    "key" => "key1",
                    "format" => "mustache"
                ]
            ],
            "callback" => false,
            "callback_url" => "callback_url",
            "callback_variable" => "callback",
            "callback_methods" => [
                "POST"
            ],
            "callback_data_types" => [
                "FORM"
            ],
            "callback_authentication" => null,
            "callback_authentication_username" => "",
            "callback_authentication_password" => "",
            "callback_whitelist" => []
        ];
        $dataSourceConfig = [
            'credentials' => [
                'wsdl' => 'http://test.processmaker.net/soap/globalweather?WSDL',
                'authentication_method' => 'password',
                'username' => 'admin',
                'password' => 'password',
                'debug_mode' => true,
            ],
        ];
        $data = [];
        $config = $this->manager->build($serviceTaskConfig, $dataSourceConfig, $data);
        $this->assertEquals('http://test.processmaker.net/soap/globalweather?WSDL', $config['wsdl']);
        $this->assertEquals('password', $config['authentication_method']);
        $this->assertEquals('admin', $config['username']);
        $this->assertEquals('password', $config['password']);
        $this->assertEquals('list', $config['endpoint']);
        $this->assertEquals(true, $config['debug_mode']);
        $this->assertEquals('PingRs._', $config['dataMapping'][0]['value']);
        $this->assertEquals('response', $config['dataMapping'][0]['key']);
        $this->assertEquals('dotNotation', $config['dataMapping'][0]['format']);
        $this->assertEquals('{{form_input_1}}', $config['outboundConfig'][0]['value']);
        $this->assertEquals('key1', $config['outboundConfig'][0]['key']);
        $this->assertEquals('mustache', $config['outboundConfig'][0]['format']);
        $this->assertEquals(false, $config['callback']);
        $this->assertEquals('callback_url', $config['callback_url']);
        $this->assertEquals('callback', $config['callback_variable']);
        $this->assertEquals([
            "POST"
        ], $config['callback_methods']);
        $this->assertEquals([
            "FORM"
        ], $config['callback_data_types']);
        $this->assertEquals(null, $config['callback_authentication']);
        $this->assertEquals('', $config['callback_authentication_username']);
        $this->assertEquals('', $config['callback_authentication_password']);
        $this->assertEquals([], $config['callback_whitelist']);
    }
}
