<?php

namespace ProcessMaker\ImportExport;

class Dependent
{
    public $type;

    public $uuid;

    public $meta;

    public function __construct(string $type, string $uuid, Manifest $manifest, $meta = null)
    {
        $this->type = $type;
        $this->uuid = $uuid;
        $this->manifest = $manifest;
        $this->meta = $meta;
    }

    public function toArray()
    {
        return [
            'type' => $this->type,
            'uuid' => $this->uuid,
            'meta' => $this->meta,
        ];
    }

    public static function fromArray(array $array, Manifest $manifest)
    {
        return array_map(function ($dependent) use ($manifest) {
            return new self($dependent['type'], $dependent['uuid'], $manifest, $dependent['meta']);
        }, $array);
    }

    public function __get($property)
    {
        $asset = $this->manifest->get($this->uuid);
        if (!$asset) {
            return null;
        }

        return $asset->$property;
    }
}
