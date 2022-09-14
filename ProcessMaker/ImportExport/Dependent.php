<?php

namespace ProcessMaker\ImportExport;

class Dependent
{
    public $type;

    public $uuid;

    public function __construct(string $type, string $uuid)
    {
        $this->type = $type;
        $this->uuid = $uuid;
    }
}
