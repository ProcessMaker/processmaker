<?php

namespace ProcessMaker\Managers;

use Exception;
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
                    'wsdl' => $config['wsdl'],
                    'options' => array_merge(self::base_options, [
                        'authentication_method' => $config['authentication_method'],
                        'login' => $config['username'],
                        'password' => $config['password'],
                    ]),
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
