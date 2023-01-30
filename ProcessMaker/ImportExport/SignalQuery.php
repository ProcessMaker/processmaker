<?php

namespace ProcessMaker\ImportExport;

class SignalQuery
{
    public function __construct(public array $signalInfo)
    {
    }

    public function withTrashed()
    {
        return $this;
    }

    public function firstOrFail()
    {
    }

    public function exists()
    {
    }
}
