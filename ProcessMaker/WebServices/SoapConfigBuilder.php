<?php

namespace ProcessMaker\WebServices;

use Exception;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\WebServices\Contracts\WebServiceConfigBuilderInterface;

class SoapConfigBuilder implements WebServiceConfigBuilderInterface
{
    private $mustache = null;

    public function build(array $serviceTaskConfig, array $dataSourceConfig, array $data): array
    {
        $config = $serviceTaskConfig;
        $credentials = $dataSourceConfig['credentials'];

        if (!$credentials) {
            throw new Exception('Credentials are required');
        }

        switch ($dataSourceConfig['authtype']) {
            case 'PASSWORD':
                $config['wsdl'] = $dataSourceConfig['credentials']['service_url'];
                break;
            case 'WSDL_FILE':
                $config['wsdl'] = Storage::disk('web_services')->path($dataSourceConfig['wsdlFile']['path']) ?? null;
                break;
            default:
                // code...
                break;
        }

        $config['username'] = $credentials['user'];
        $config['password'] = $credentials['password'];
        $config['authentication_method'] = $dataSourceConfig['authtype'] ?? 'PASSWORD';
        $config['debug_mode'] = $dataSourceConfig['debug_mode'];
        $config['location'] = $credentials['location'] ?? '';
        $config['service_url'] = $credentials['service_url'] ?? '';
        $config['password_type'] = $credentials['password_type'] ?? 'None';
        // Prepare endpoint params and dataMapping
        if (!empty($serviceTaskConfig['endpoint'])) {
            $endpoint = $serviceTaskConfig['endpoint'];
            $endpointDefinition = $dataSourceConfig['endpoints'][$endpoint];
            $config['operation'] = $endpointDefinition['operation'];
            $outboundConfig = $serviceTaskConfig['outboundConfig'];
            $parameters = $data;
            foreach ($outboundConfig as $map) {
                switch ($map['key']) {
                    case 'ObjectDef':
                        $evaluated = ExpressionEvaluator::evaluate('mustache', $map['value'], $data);
                        $parameters = json_decode($evaluated, true);
                        break;
                    case 'RequestVariable':
                        $value = ExpressionEvaluator::evaluate('feel', $map['value'], $data);
                        $parameters = json_decode(json_encode($value), true);
                        break;
                    default:
                        $parameters = json_decode(json_encode($map['value']));
                }
            }
            $config['parameters'] = $parameters;
        } else {
            $config['operation'] = '';
            $config['parameters'] = [];
        }

        if (!empty($config['isTest']) && $config['isTest'] === true) {
            $config['parameters'] = $data;
        }

        return $config;
    }
}
