<?php

namespace ProcessMaker\ImportExport\Entities;

use ProcessMaker\ImportExport\AssetStore;
use ProcessMaker\ImportExport\Contracts\Entity as EntityInterface;

abstract class Entity implements EntityInterface
{
    public $references = [];

    public function __construct(
        public string $name,
        public array $params,
        public array $definition,
        public AssetStore $assetStore,
    ) {
    }

    public function setReferences($references)
    {
        $this->references = $references;
    }
    
    public function getReferences()
    {
        return $this->references;
    }
}
