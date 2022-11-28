<?php

namespace ProcessMaker\ImportExport;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Contracts\Entity;
use ProcessMaker\ImportExport\Contracts\Strategy;

class Dependent {
    public function __construct(
        public Strategy $strategy,
        public Asset $asset,
        public array $meta
    ) {
    }

    public function toArray() {
        return [
            'strategy' => get_class($this->strategy),
            'asset' => $this->asset->id(),
            'meta' => $this->meta,
        ];
    }
}