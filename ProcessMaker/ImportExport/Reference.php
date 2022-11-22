<?php

namespace ProcessMaker\ImportExport;

use ProcessMaker\ImportExport\Contracts\Entity;
use ProcessMaker\ImportExport\Contracts\Strategy;

class Reference
{
    public function __construct(
        public Entity $entity,
        public Strategy $strategy
    ) {
    }
}
