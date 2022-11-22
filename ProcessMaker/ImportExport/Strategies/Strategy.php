<?php

namespace ProcessMaker\ImportExport\Strategies;

use ProcessMaker\ImportExport\Contracts\Entity;
use ProcessMaker\ImportExport\Contracts\Strategy as StrategyInterface;
use ProcessMaker\ImportExport\Reference;

class Strategy implements StrategyInterface
{
    public Reference $reference;

    public function __construct(
        public Entity $source,
        public array $params
    ) {
    }

    public function setReference(Reference $reference) : void
    {
        $this->reference = $reference;
    }
}
