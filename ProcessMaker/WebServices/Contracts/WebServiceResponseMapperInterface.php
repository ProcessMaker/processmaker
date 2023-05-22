<?php

namespace ProcessMaker\WebServices\Contracts;

interface WebServiceResponseMapperInterface
{
    public function map($response, array $config, array $data): array;
}
