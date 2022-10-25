<?php

namespace ProcessMaker\WebServices\Contracts;

interface WebServiceRequestBuilderInterface
{
    public function build(array $config, array $requestData): array;
}
