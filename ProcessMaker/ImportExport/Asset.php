<?php

namespace ProcessMaker\ImportExport;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Contracts\Entity;

class Asset {
    public function __construct(
        public AssetStore $assetStore,
        public Entity $entity,
        public $id,
        public $payload,
        public $meta = [],
        public $dependents = [],
    ) {
    }

    public function toArray() {
        return [
            'entity' => $this->entity->name,
            'id' => $this->entity->id,
            'payload' => $this->payload,
            'meta' => $this->meta,
            'dependents' => Arr::map($this->dependents, fn($d) => $d->toArray()),
        ];
    }

    public function addDependent(Dependent $dependent) {
        $this->dependents[] = $dependent;
    }

    public function id()
    {
        return $this->assetStore->key($this->entity, $this->id);
    }
}