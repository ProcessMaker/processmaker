<?php

namespace ProcessMaker\ImportExport;

use ProcessMaker\ImportExport\Contracts\Entity;

class EntityStore
{
    private $entities;

    public function get($name) : Entity
    {
        return $this->entities[$name];
    }

    public function add(Entity $entity) : void
    {
        $this->entities[$entity->name] = $entity;
    }
    
    public function all() : array
    {
        return $this->entities;
    }
}
