<?php

namespace ProcessMaker\Managers;

use ProcessMaker\Contracts\SoapClientInterface;
use ProcessMaker\Contracts\WebServiceCallerInterface;

class WebServiceSoapServiceCaller implements WebServiceCallerInterface
{
    public function call(array $request): array
    {
        $client = app(SoapClientInterface::class, $request);
        $response = $client->callMethod($request['method'], $request['parameters']);
        return $response;
    }
}
