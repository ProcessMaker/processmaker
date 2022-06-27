<?php

namespace ProcessMaker\WebServices\Contracts;

interface WebServiceCallerInterface
{
    public function call($config, ...$request);
}