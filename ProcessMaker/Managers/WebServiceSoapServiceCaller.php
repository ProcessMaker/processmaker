<?php

namespace ProcessMaker\Managers;

use ProcessMaker\Contracts\SoapClientInterface;
use ProcessMaker\Contracts\WebServiceCallerInterface;

class WebServiceSoapServiceCaller implements WebServiceCallerInterface
{
    public function call(array $request)
    {
        $client = app(SoapClientInterface::class, $request);
        if (isset($request['service_port'])) {
            $client->selectServicePort($request['service_port']);
        }
        $response = $client->callMethod($request['method'], $request['parameters']);
        return $response;
    }
}
