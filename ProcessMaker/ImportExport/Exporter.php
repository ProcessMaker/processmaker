<?php

namespace ProcessMaker\ImportExport;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\ImportExport\Exporters\ScreenExporter;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;

class Exporter
{
    public function export(Model $model, string $exporterClass)
    {
        $this->manifest = new Manifest();
        $this->rootExporter = new $exporterClass($model, $this->manifest);
        $this->manifest->push($model->uuid, $this->rootExporter);
        $this->rootExporter->runExport();

        return $this->rootExporter;
    }

    public function exportScreen(Screen $screen)
    {
        return $this->export($screen, ScreenExporter::class);
    }

    public function exportProcess(Process $process)
    {
        return $this->export($process, ProcessExporter::class);
    }

    public function payload($password = null)
    {
        $export = $this->manifest->toArray();

        $payload = [
            'type' => 'screen_package',
            'version' => '2',
            'root' => $this->rootExporter->uuid(),
            'export' => $export,
        ];

        if ($password) {
            $payload = (new ExportEncrypted($password))->call($payload);
        }

        return $payload;
    }

    public function tree()
    {
        return (new Tree($this->manifest))->tree($this->rootExporter);
    }
}
