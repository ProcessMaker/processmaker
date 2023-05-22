<?php

namespace ProcessMaker\WebServices\Contracts;

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

    /**
     * Get a list of service ports available
     *
     * @return array
     */
    public function getServices(): array;

    /**
     * Get a list of operations available
     *
     * @param string $serviceName If empty, all operations will be returned
     *
     * @return array
     */
    public function getOperations(string $serviceName = ''): array;

    /**
     * Get a list of types available
     *
     * @return array
     */
    public function getTypes(): array;

    /**
     * Select a service port
     *
     * @param string $portName
     *
     * @return void
     */
    public function selectServicePort(string $portName);
}
