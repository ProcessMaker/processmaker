<?php

namespace ProcessMaker\Soap;

use DOMDocument;
use DOMXPath;
use ProcessMaker\Contracts\SoapClientInterface;
use SoapClient;
use SoapHeader;
use SoapVar;

class NativeSoapClient implements SoapClientInterface
{
    private $soapClient;
    private $services = [];

    public function __construct(string $wsdl, array $options)
    {
        $this->soapClient = new SoapClient($wsdl, $options);
        // Parse WSDL
        $dom = new DOMDocument();
        $dom->load($wsdl);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/wsdl/soap/');
        $xpath->registerNamespace('wsdl', 'http://schemas.xmlsoap.org/wsdl/');
        // Get Service Ports
        $xpathQuery = 'wsdl:service/wsdl:port[@name]/soap:address';
        $servicePorts = $xpath->query($xpathQuery);
        foreach ($servicePorts as $servicePort) {
            $serviceName = $servicePort->parentNode->getAttribute('name');
            $servicePortLocation = $servicePort->getAttribute('location');
            $this->services[$serviceName] = [
                'name' => $serviceName,
                'location' => $servicePortLocation,
            ];
        }
        // Add Soap Auth Headers
        $this->addSoapAuthHeaders($options);
    }

    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * Execute a Soap Method
     *
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     */
    public function callMethod(string $method, array $parameters)
    {
        $response = $this->soapClient->__soapCall($method, $parameters);
        return $response;
    }

    public function selectServicePort(string $portName)
    {
        $this->soapClient->__setLocation($this->services[$portName]['location']);
    }

    private function addSoapAuthHeaders(array $options)
    {
        switch ($options['authentication_method']) {
            case 'password':
                $this->soapClient->__setSoapHeaders([
                    new SoapHeader(
                        'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd',
                        'Security',
                        new SoapVar(
                            '<wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
                                <wsse:UsernameToken>
                                    <wsse:Username>' . $options['login'] . '</wsse:Username>
                                    <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . $options['password'] . '</wsse:Password>
                                </wsse:UsernameToken>
                            </wsse:Security>',
                            XSD_ANYXML
                        )
                    ),
                ]);
                break;
        }
    }

    public function getOperations(string $serviceName = ''): array
    {
        $response = $this->soapClient->__getFunctions();
        return $response;
    }
}
