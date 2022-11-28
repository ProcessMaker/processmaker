<?php

namespace ProcessMaker\ImportExport;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Asset;
use ProcessMaker\ImportExport\Contracts\Entity;

class AssetStore
{
    private $items;

    public function add(Asset $asset) : void
    {
        $this->items[$asset->id()] = $asset;
    }

    public function hasByEntitiyAndId(Entity $entity, $id) : bool
    {
        $key = $this->key($entity, $id);
        return Arr::has($this->items, $key);
    }
    
    public function getByEntitiyAndId(Entity $entity, $id) : Asset|null
    {
        $key = $this->key($entity, $id);
        return Arr::get($this->items, $key, null);
    }

    public function key(Entity $entity, $id) : string
    {
        return $entity->name . '_' . (string) $id;
    }

    public function toArray()
    {
        return $this->items;
    }
}
