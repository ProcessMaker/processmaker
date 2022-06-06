<?php

namespace ProcessMaker\Managers;

use Illuminate\Support\Facades\Storage;
use ProcessMaker\Contracts\WebServiceConfigBuilderInterface;

class WebServiceSoapConfigBuilder implements WebServiceConfigBuilderInterface
{
    public function build(array $originalConfig): array
    {
        $config = $originalConfig;
        return $config;
    }
}
