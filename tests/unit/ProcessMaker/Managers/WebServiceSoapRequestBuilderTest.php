<?php

namespace ProcessMaker\Managers;

use jamesiarmes\PhpNtlm\SoapClient;
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

    public function testNtlm()
    {
        $file = '/Users/dipo/Workspace/Integrations/Jack Henry/R2022.0.02TPGPub/Validation/TPG_Customer.wsdl';
        $client = new SoapClient($file, [
            'trace' => true,
            'exceptions' => false,
            'user' => 'username1',
            'password' => '123456',
            'domain' => 'JXTEST',
        ]);
        $client->__setLocation('https://jxtest.jackhenry.com/jxchange/2008/ServiceGateway/ServiceGateway.svc');
        $rr = $client->__soapCall('Ping', [
            'body' => [
                'PingRq' => '?',
            ],
        ]);
        dump($rr);
    }
}


https://jxtest.jackhenry.com/jxchange/2008/ServiceGateway/ServiceGateway.svc
https://jxtest.jackhenry.com/jxchange/2008/ServiceGateway/Customer.svc