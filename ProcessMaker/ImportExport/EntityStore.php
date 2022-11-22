<?php

namespace ProcessMaker\ImportExport;

use ProcessMaker\ImportExport\Contracts\Entity;

class EntityStore
{
    private $entities;

    public function get($name) : Entity|null
    {
        if (!isset($this->entities[$name])) {
            return null;
        }

        return $this->entities[$name];
    }

    public function set(string $name, Entity $entity) : void
    {
        $this->entities[$name] = $entity;
    }
}
