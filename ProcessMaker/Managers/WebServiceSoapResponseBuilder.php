<?php

namespace ProcessMaker\Managers;

use ProcessMaker\Contracts\WebServiceResponseMapperInterface;

class WebServiceSoapResponseBuilder implements WebServiceResponseMapperInterface
{

    /**
     * Map the response from the WebService to request data
     *
     * @param mixed $response
     * @param array $config
     * @param array $data
     *
     * @return array
     */
    public function map($response, array $config, array $data): array
    {
        //**
//        $result = [
//            'response' => $response,
//        ];

        $requestVariable = !empty($config['dataMapping']) && count($config['dataMapping']) > 0
            ? $config['dataMapping'][0]['key']
            : 'response';
        $responseArray = json_decode(json_encode($response), true);
        return (array_key_exists('response', $responseArray))
            ? $response
            : [$requestVariable => $responseArray, 'status' => 200];
    }
}
