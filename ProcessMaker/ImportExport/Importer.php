<?php

namespace ProcessMaker\ImportExport;

use Illuminate\Support\Facades\DB;

class Importer
{
    public $options;


    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    public function prviewImport(array $import)
    {
        $manifest = $this->loadManifest($import);
        return $manifest->toArray();
    }

    public function loadManifest(array $import)
    {
        return Manifest::fromArray($import['export'], $this->options);
    }

    private function doImport($exporters)
    {
        // imports must happen on leaf nodes and go backwards from there??
        DB::transaction(function() {
        });
    }

    public function tree(array $import)
    {
        $manifest = $this->loadManifest($import);
        $rootExporter = $manifest->get($import['root']);
        return (new Tree($manifest))->tree($rootExporter);
    }

}
