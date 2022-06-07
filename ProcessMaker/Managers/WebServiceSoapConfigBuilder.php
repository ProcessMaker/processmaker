<?php

namespace ProcessMaker\Managers;

use ProcessMaker\Contracts\WebServiceConfigBuilderInterface;

class WebServiceSoapConfigBuilder implements WebServiceConfigBuilderInterface
{
    public function build(array $serviceTaskConfig, array $dataSourceConfig, array $data): array
    {
        $config = $serviceTaskConfig;
        $credentials = $dataSourceConfig['credentials'];
        $config['wsdl'] = $credentials['wsdl'];
        $config['username'] = $credentials['username'];
        $config['password'] = $credentials['password'];
        $config['authentication_method'] = $credentials['authentication_method'];
        $config['debug_mode'] = $credentials['debug_mode'];
        return $config;
    }
}
