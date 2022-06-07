<?php

namespace ProcessMaker\Factories;

use ProcessMaker\Contracts\SoapClientInterface;
use ProcessMaker\WebServices\NativeSoapClient;

class SoapClientFactory
{
    public function createNativeSoapClient(string $wsdl, array $options): SoapClientInterface
    {
        return new NativeSoapClient($wsdl, $options);
    }
}
