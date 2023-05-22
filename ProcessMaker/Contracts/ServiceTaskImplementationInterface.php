<?php

namespace ProcessMaker\Contracts;

/**
 * Can be executed as a ServiceTask
 */
interface ServiceTaskImplementationInterface
{
    /**
     * Run the service task implementation
     *
     * @param array $data
     * @param array $config
     * @param string $tokenId
     *
     * @return string $tokenId
     */
    public function run(array $data, array $config, $tokenId = '', $errorHandling = []);
}
