<?php

namespace ProcessMaker\Contracts;

interface WebServiceCallerInterface
{
    /**
     * Execute the Soap Request
     *
     * @param array $request
     *
     * @return mixed
     */
    public function call(array $request);
}
