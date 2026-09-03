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
     * @return mixed
     *
     * Implementations may accept an optional 4th argument `$timeout` (seconds).
     * WorkflowManager passes it when available (see CoreServiceTask).
     */
    public function run(array $data, array $config, $tokenId = '');
}
