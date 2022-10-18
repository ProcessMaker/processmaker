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
                        'location' => null,
                        'debug_mode' => $config['debug_mode'],
                        'password_type' => $config['password_type'],
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
                $parameters = [
                    'wsdl' => $config['wsdl'],
                    'options' => array_merge(self::base_options, [
                        'authentication_method' => $config['authentication_method'],
                        'keep_alive'    => true,
                        'trace'         => true,
                        'location'      => $config['location'],
                        'local_cert'    => $config['local_cert'],
                        'passphrase'    => $config['passphrase'],
                        'cache_wsdl'    => WSDL_CACHE_NONE,
                        'exceptions'    => true,
                        'stream_context' => stream_context_create(array(
                            'ssl' => array(
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true
                            )))
                    ]),
                    'operation' => $config['operation'],
                    'parameters' => $config['parameters'],
                ];

                break;
            default:
                throw new Exception('Invalid authentication method: ' . $config['authentication_method']);
        }

        return $parameters;
    }
}
