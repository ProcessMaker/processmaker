<?php

namespace ProcessMaker\Contracts;

interface WebServiceConfigBuilderInterface
{
    /**
     * Build the configuration for the WebService
     *
     * @param array $originalConfig
     *
     * @return array
     */
    public function build(array $originalConfig): array;
}
