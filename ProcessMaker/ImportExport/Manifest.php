<?php

namespace ProcessMaker\ImportExport;

use ProcessMaker\ImportExport\Exporters\ExporterInterface;

class Manifest
{
    private $manifest = [];

    public function has(string $uuid)
    {
        return array_key_exists($uuid, $this->manifest);
    }

    public function get($uuid)
    {
        return $this->manifest[$uuid];
    }

    public function getAll()
    {
        return $this->manifest;
    }

    public function push(string $uuid, ExporterInterface $exporter)
    {
        $this->manifest[$uuid] = $exporter;
    }
}
