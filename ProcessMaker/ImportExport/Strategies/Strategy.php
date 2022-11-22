<?php

namespace ProcessMaker\ImportExport\Strategies;

use ProcessMaker\ImportExport\Contracts\Entity;

class Strategy
{
    public function __construct(
        public Entity $source,
        public Entity $destination,
        public array $params
    ) {
    }
}
