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
        $paramsAsArray = json_decode(json_encode($request['parameters']), true);
        //$response = $client->callMethod($request['operation']['value'], ['body' => (array) $request['parameters']]);
        $response = $client->callMethod($request['operation']['value'], ['body' => $paramsAsArray]);
        return $response;
    }
}
