<?php

namespace ProcessMaker\ImportExport;

use Illuminate\Support\Facades\DB;

class Importer
{
    public $options;

    public $payload;

    public $manifest;

    public function __construct(array $payload, Options $options)
    {
        $this->payload = $payload;
        $this->options = $options;
        $this->manifest = $this->loadManifest();
    }

    public function prviewImport()
    {
        return $this->manifest->toArray();
    }

    public function loadManifest()
    {
        return Manifest::fromArray($this->payload['export'], $this->options);
    }

    public function doImport()
    {
        DB::transaction(function () {
            foreach ($this->manifest->orderForImport() as $uuid) {
                $exporter = $this->manifest->get($uuid);
                $exporter->import();
            }
        });
    }

    public function tree()
    {
        $rootExporter = $this->manifest->get($this->payload['root']);

        return (new Tree($this->manifest))->tree($rootExporter);
    }
}
