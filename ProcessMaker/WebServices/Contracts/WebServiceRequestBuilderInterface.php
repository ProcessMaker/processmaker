<?php

namespace ProcessMaker\WebServices\Contracts;

interface WebServiceRequestBuilderInterface
{
    public function build($config, $requestData, $client);
}