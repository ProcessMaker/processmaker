<?php

namespace ProcessMaker\WebServices;

use Exception;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\WebServices\Contracts\WebServiceRequestBuilderInterface;

class SoapRequestBuilder implements WebServiceRequestBuilderInterface
{
    const base_options = [
        'exceptions' => true,
        'trace' => true,
    ];

    public function build(array $config, array $data): array
    {
        switch ($config['authentication_method']) {
            case 'PASSWORD':
                $parameters = [
                    'wsdl' => $config['wsdl'],
                    'options' => array_merge(self::base_options, [
                        'authentication_method' => $config['authentication_method'],
                        'login' => $config['username'],
                        'password' => $config['password'],
                        'location' => null ,
                        'debug_mode' => $config['debug_mode'],
                    ]),
                    'operation' => $config['operation'],
                    'parameters' => $config['parameters'],
                ];
                break;
            case 'WSDL_FILE':
                $parameters = [
                    'wsdl' => $config['wsdl'],
                    'options' => array_merge(self::base_options, [
                        'authentication_method' => $config['authentication_method'],
                        'login' => $config['username'],
                        'password' => $config['password'],
                        'location' => $config['location'],
                        'debug_mode' => $config['debug_mode'],
                    ]),
                    'operation' => $config['operation'],
                    'parameters' => $config['parameters'],
                ];
                break;
            case 'LOCAL_CERTIFICATE':
                break;
            default:
                throw new Exception('Invalid authentication method: ' . $config['authentication_method']);
        }

        return $parameters;
    }
}
