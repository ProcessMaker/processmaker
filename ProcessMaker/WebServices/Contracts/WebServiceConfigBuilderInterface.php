<?php

namespace ProcessMaker\WebServices\Contracts;

interface WebServiceConfigBuilderInterface
{
    public function build($connectorConfig, $dataSourceConfig, $requestData);
}