<?php

namespace ProcessMaker\ImportExport;

class Dependent
{
    public $type;

    public $uuid;

    public function __construct(string $type, string $uuid, Manifest $manifest)
    {
        $this->type = $type;
        $this->uuid = $uuid;
        $this->manifest = $manifest;
    }

    public function toArray()
    {
        return [
            'type' => $this->type,
            'uuid' => $this->uuid,
        ];
    }

    public static function fromArray(array $array, Manifest $manifest)
    {
        return array_map(function ($dependent) use ($manifest) {
            return new self($dependent['type'], $dependent['uuid'], $manifest);
        }, $array);
    }

    public function __get($property)
    {
        if ($property == 'model') {
            return $this->manifest->get($this->uuid)->model;
        }
        if ($property == 'originalId') {
            return $this->manifest->get($this->uuid)->originalId;
        }
    }
}
