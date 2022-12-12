<?php

namespace ProcessMaker\WebServices\Contracts;

interface WebServiceConfigBuilderInterface
{
    public function build(array $connectorConfig, array $dataSourceConfig, array $requestData);
}
