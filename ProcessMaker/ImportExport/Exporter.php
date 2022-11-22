<?php

namespace ProcessMaker\ImportExport;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ProcessMaker\ImportExport\Exporters\ProcessExporter;
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

    public function payload($password = null): array
    {
        $export = $this->manifest->toArray();

        $payload = [
            'type' => $this->rootExporter->getType(),
            'version' => '2',
            'root' => $this->rootExporter->uuid(),
            'name' => $this->rootExporter->getName(),
            'export' => $export,
        ];

        if ($password) {
            $payload = $this->encrypt($password, $payload);
        }

        return $payload;
    }

    public function encrypt($password, $payload)
    {
        return (new ExportEncrypted($password))->call($payload);
    }

    public function tree(): array
    {
        return (new Tree($this->manifest))->tree($this->rootExporter);
    }

    public function exportInfo(array $manifest): string
    {
        $exported = collect($manifest['export'])
            ->groupBy(function ($item) {
                $model = Str::afterLast($item['model'], '\\');

                return Str::snake(Str::pluralStudly($model));
            })
            ->map(function ($group) {
                return $group->pluck('attributes.id');
            });

        return json_encode([
            'exported' => $exported,
        ]);
    }
}
