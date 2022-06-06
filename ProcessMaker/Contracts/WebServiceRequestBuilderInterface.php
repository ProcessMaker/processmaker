<?php

namespace ProcessMaker\Contracts;

interface WebServiceRequestBuilderInterface
{
    /**
     * Build the WebService request
     *
     * @param array $config
     * @param array $data
     *
     * @return array
     */
    public function build(array $config, array $data): array;
}
