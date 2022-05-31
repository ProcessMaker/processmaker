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
     * @return mixed
     */
    public function build(array $config, array $data);
}
