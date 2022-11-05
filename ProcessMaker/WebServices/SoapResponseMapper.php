<?php

namespace ProcessMaker\WebServices;

use ProcessMaker\Models\FormalExpression;
use ProcessMaker\WebServices\Contracts\WebServiceResponseMapperInterface;

class SoapResponseMapper implements WebServiceResponseMapperInterface
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
        $responseArray = json_decode(json_encode($response), true);

        if (array_key_exists('response', $responseArray)) {
            return $response;
        }

        if ($config['isTest'] ?? false) {
            return ['response' => $responseArray, 'status' => 200];
        }

        $mappings = $config['dataMapping'] ?? [];

        $result = [];
        foreach ($mappings as $mapping) {
            if (!empty($mapping['value'])) {
                $result[$mapping['key']] = ExpressionEvaluator::evaluate('feel', $mapping['value'], $responseArray);
            } else {
                $result[$mapping['key']] = $responseArray;
            }
        }

        return $result;
    }
}
