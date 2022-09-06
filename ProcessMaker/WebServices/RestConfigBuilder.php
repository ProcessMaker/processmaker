<?php

namespace ProcessMaker\WebServices;

class RestConfigBuilder implements Contracts\WebServiceConfigBuilderInterface
{
    public function build($connectorConfig, $dataSourceConfig, $requestData)
    {
        // The returned value
        $config = $connectorConfig;

        $outboundConfig = $connectorConfig['outboundConfig'] ?? [];
        $endpoint = $dataSourceConfig['endpoints'][$connectorConfig['endpoint']];

        $config['verifySsl'] = array_key_exists('verify_certificate', $dataSourceConfig)
            ? $dataSourceConfig['verify_certificate']
            : true;

        $config['endpoint'] = $endpoint;

        // Prepare URL
        $config['params'] = $this->prepareBodyData($requestData, $outboundConfig, 'PARAM');
        $config['method'] = ExpressionEvaluator::evaluate('mustache', $endpoint['method'], $requestData);
        $config['headers'] = $this->headers($endpoint, $connectorConfig, $requestData);
        $data = $this->prepareBodyData($requestData, $outboundConfig, 'BODY', $requestData);

        $config['body'] = ExpressionEvaluator::evaluate('mustache', $endpoint['body'], $data);
        $config['bodyType'] = null;
        if (isset($endpoint['body_type'])) {
            $config['bodyType'] = ExpressionEvaluator::evaluate('mustache', $endpoint['body_type'], $data);
        }

        //add DataSource configurations
        $config['authtype'] = $dataSourceConfig['authtype'];
        $config['credentials'] = $dataSourceConfig['credentials'];
        $config['endpoints'] = $dataSourceConfig['endpoints'];

        return $config;
    }

    /**
     * Prepare data to be used in body (mustache)
     *
     * @param array $requestData
     * @param array $outboundConfig
     * @param string $type PARAM HEADER BODY
     * @param array $data initial data
     *
     * @return array
     */
    private function prepareBodyData(array $requestData, array $outboundConfig, $type, $data = [])
    {
        foreach ($outboundConfig as $outbound) {
            if ($outbound['type'] === $type) {
                // By default we use moustache
                $expressionType = 'mustache';
                if (isset($outbound['format']) && $outbound['format'] === 'feel') {
                    $expressionType = 'feel';
                }
                $data[$outbound['key']] = ExpressionEvaluator::evaluate($expressionType, $outbound['value'], $requestData);
            }
        }

        return $data;
    }

    private function headers($endpoint, array $config, array $data)
    {
        $headers = ['Accept' => 'application/json'];

        // evaluate headers defined in the data source
        if (!array_key_exists('outboundConfig', $config)) {
            if (isset($endpoint['headers']) && is_array($endpoint['headers'])) {
                foreach ($endpoint['headers'] as $header) {
                    $headers[$this->getMustache()->render($header['key'], $data)] = $this->getMustache()->render($header['value'], $data);
                }
            }

            return $headers;
        }

        // mix data source and connector config headers
        $outboundConfig = $config['outboundConfig'];
        $dataSourceParams = array_filter($endpoint['headers'], function ($item) {
            return $item['required'] === true;
        });
        $configParams = array_filter($outboundConfig, function ($item) {
            return $item['type'] === 'HEADER';
        });

        foreach ($configParams as $cfgParam) {
            $existsInDataSourceParams = false;
            foreach ($dataSourceParams as &$param) {
                if ($param['key'] === $cfgParam['key']) {
                    $param['value'] = $cfgParam['value'];
                    $existsInDataSourceParams = true;
                }
            }
            if (!$existsInDataSourceParams) {
                array_push($dataSourceParams, [
                    'key' => $cfgParam['key'],
                    'value' => $cfgParam['value'],
                ]);
            }
        }
        foreach ($dataSourceParams as $header) {
            $headerKey = ExpressionEvaluator::evaluate('mustache', $header['key'], $data);
            $headers[$headerKey] = ExpressionEvaluator::evaluate('mustache', $header['value'], $data);
        }

        return $headers;
    }
}
