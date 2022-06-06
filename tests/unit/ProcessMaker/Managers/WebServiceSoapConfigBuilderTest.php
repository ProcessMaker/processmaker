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

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new WebServiceSoapConfigBuilder;
    }

    public function testBuildUserPasswordAuthRequest()
    {
        $originalConfig = [
            'wsdl' => 'http://test.processmaker.net/soap/globalweather?WSDL',
            'authentication_method' => 'password',
            'username' => 'admin',
            'password' => 'password',
            'debug_mode' => true,
        ];
        $config = $this->manager->build($originalConfig);
        $this->assertEquals('http://test.processmaker.net/soap/globalweather?WSDL', $config['wsdl']);
        $this->assertEquals('password', $config['authentication_method']);
        $this->assertEquals('admin', $config['username']);
        $this->assertEquals('password', $config['password']);
        $this->assertEquals(true, $config['debug_mode']);
    }
}
