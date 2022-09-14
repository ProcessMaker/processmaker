<?php

namespace ProcessMaker\ImportExport;

use ProcessMaker\ImportExport\Exporters\ScreenExporter;
use ProcessMaker\Models\Screen;

class Exporter
{
    public $manifest;

    private $rootExporter;

    public function exportScreen(Screen $screen)
    {
        $this->manifest = new Manifest();
        $this->rootExporter = new ScreenExporter($screen, $this->manifest);
        $this->manifest->push($screen->uuid, $this->rootExporter);
        $this->rootExporter->export();
    }

    public function tree()
    {
        return $this->rootExporter->tree();
    }
}
