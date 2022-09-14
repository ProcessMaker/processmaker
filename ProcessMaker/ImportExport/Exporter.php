<?php

namespace ProcessMaker\ImportExport;

use ProcessMaker\ImportExport\Exporters\ScreenExporter;
use ProcessMaker\Models\Screen;

class Exporter
{
    public function export($model, $exporterClass)
    {
        $manifest = new Manifest();
        $rootExporter = new $exporterClass($model, $manifest);
        $manifest->push($model->uuid, $rootExporter);
        $rootExporter->export();

        return $rootExporter->tree();
    }

    public function exportScreen(Screen $screen)
    {
        return $this->export($screen, ScreenExporter::class);
    }
}
