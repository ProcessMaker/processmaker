<?php

namespace ProcessMaker\ImportExport\Entities;

use ProcessMaker\ImportExport\Contracts\Entity as EntityInterface;

class Entity implements EntityInterface
{
    public $references = [];

    public function __construct(
        public array $params,
    ) {
    }

    public function setReferences($references)
    {
        $this->references = $references;
    }
}
