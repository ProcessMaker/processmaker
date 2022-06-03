<?php

namespace ProcessMaker\Managers;

use Tests\TestCase;

class WebServiceSoapRequestBuilderTest extends TestCase
{

    /**
     *
     * @var WebServiceSoapRequestBuilder
     */
    private $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new WebServiceSoapRequestBuilder;
    }

    public function testBuildUserPasswordAuthRequest()
    {
        $config = [
            'wsdl' => 'http://test.processmaker.net/soap/globalweather?WSDL',
            'authentication_method' => 'password',
            'username' => 'admin',
            'password' => 'password',
        ];
        $data = [];
        $config = $this->manager->build($config, $data);
        $this->assertEquals('http://test.processmaker.net/soap/globalweather?WSDL', $config['wsdl']);
        $this->assertEquals('admin', $config['options']['login']);
        $this->assertEquals('password', $config['options']['password']);
    }
}
