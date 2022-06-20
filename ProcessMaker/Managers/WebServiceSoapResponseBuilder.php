<?php

namespace ProcessMaker\Managers;

use ProcessMaker\Contracts\WebServiceResponseMapperInterface;
use ProcessMaker\Models\FormalExpression;

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
        $responseArray = json_decode(json_encode($response), true);

        if (array_key_exists('response', $responseArray)) {
            return $response;
        }

        if ($config['isTest'] ?? false) {
            return ['response' => $responseArray, 'status' => 200];
        }

        $mappings = $config['dataMapping'] ?? [];

        $result = [];
        foreach($mappings as $mapping) {
            if (!empty($mapping['value'])) {
                $result[$mapping['key']] = $this->evalExpression($mapping['value'], $responseArray) ;
            }
            else {
                $result[$mapping['key']] = $responseArray ;
            }
        }

        return $result;
    }

    private function evalExpression($expression, array $data)
    {
        try {
            $formal = new FormalExpression();
            $formal->setBody($expression);
            return $formal($data);
        } catch (Exception $exception) {
            return "{$expression}: " . $exception->getMessage();
        }
    }
}
