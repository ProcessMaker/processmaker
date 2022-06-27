<?php

namespace ProcessMaker\WebServices\Contracts;

interface WebServiceResponseMapperInterface
{
    //TODO remove dsConfig
    public function map($response, $responseStatus, $headers, $config, $dsConfig, $requestData) : array;
}