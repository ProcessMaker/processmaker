<?php

namespace ProcessMaker\Contracts;

interface SoapClientInterface
{
    /**
     * Execute a Soap Method
     *
     * @param array $method
     * @param array $parameters
     *
     * @return array
     */
    public function callMethod(string $method, array $parameters): array;
}
