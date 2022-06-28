<?php

namespace ProcessMaker\WebServices;

use ProcessMaker\WebServices\Contracts\SoapClientInterface;
use ProcessMaker\WebServices\Contracts\WebServiceCallerInterface;

class SoapServiceCaller implements WebServiceCallerInterface
{
    public function call(array $request)
    {
        $client = app(SoapClientInterface::class, $request);
        if (isset($request['service_port'])) {
            $client->selectServicePort($request['service_port']);
        }
        $paramsAsArray = json_decode(json_encode($request['parameters']), true);
        $response = $client->callMethod($request['operation']['value'], ['body' => $paramsAsArray]);
        return $response;
    }
}
