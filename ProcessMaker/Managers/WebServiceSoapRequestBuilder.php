<?php

namespace ProcessMaker\Managers;

use Exception;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Contracts\WebServiceRequestBuilderInterface;

class WebServiceSoapRequestBuilder implements WebServiceRequestBuilderInterface
{
    const base_options = [
        'exceptions' => true,
        'trace' => true,
    ];

    public function build(array $config, array $data): array
    {
        switch ($config['authentication_method']) {
            case 'password':
                $parameters = [
                    'wsdl' => Storage::disk('web_services')->path($config['wsdl']),
                    'options' => array_merge(self::base_options, [
                        'authentication_method' => $config['authentication_method'],
                        'login' => $config['username'],
                        'password' => $config['password'],
                        'location' => $config['location'],
                    ]),
                    'operation' => $config['operation'],
                    'parameters' => $config['parameters'],
                ];
            break;
            case 'certificate':
            break;
            default:
                throw new Exception('Invalid authentication method: ' . $config['authentication_method']);
        }
        return $parameters;
    }
}
