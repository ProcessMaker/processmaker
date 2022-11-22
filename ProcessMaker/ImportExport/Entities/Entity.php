<?php

namespace ProcessMaker\ImportExport\Entities;

class Entity
{
    public function __construct(
        public array $params,
        public array $references
    ) {
    }
}
