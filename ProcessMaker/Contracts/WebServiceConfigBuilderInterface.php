<?php

namespace ProcessMaker\Contracts;

interface WebServiceConfigBuilderInterface
{
    /**
     * Build the configuration for the WebService
     *
     * @param array $serviceTaskConfig
     *
     * @return array
     */
    public function build(array $serviceTaskConfig, array $dataSourceConfig, array $data): array;
}
