<?php

namespace ProcessMaker\ImportExport;

class Dependent
{
    public $type;

    public $uuid;

    public function __construct(string $type, string $uuid)
    {
        $this->type = $type;
        $this->uuid = $uuid;
    }

    public function toArray()
    {
        return [
            'type' => $this->type,
            'uuid' => $this->uuid,
        ];
    }

    public static function fromArray(array $array)
    {
        return array_map(function($dependent) {
            return new self($dependent['type'], $dependent['uuid']);
        }, $array);
    }
}
