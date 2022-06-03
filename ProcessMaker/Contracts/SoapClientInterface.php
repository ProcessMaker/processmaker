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
     * @return mixed
     */
    public function callMethod(string $method, array $parameters);

    public function getServices(): array;

    public function selectServicePort(string $portName);
}
