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
    public function export(Model $model, string $exporterClass, $options = null)
    {
        $options = $options ?: new Options([]);
        $this->manifest = new Manifest();
        $this->rootExporter = new $exporterClass($model, $this->manifest, $options);
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

    public function payload(): array
    {
        $this->manifest->runAfterExport();
        $export = $this->manifest->toArray();

        // if ($options) {
        //     $options = $options->options;
        //     $export = array_filter($export, function ($uuid) use ($options, &$discarded) {
        //         if (isset($options[$uuid])) {
        //             if ($options[$uuid]['mode'] === 'discard') {
        //                 $discarded[] = $uuid;

        //                 return false;
        //             }
        //         }

        //         return true;
        //     }, ARRAY_FILTER_USE_KEY);
        // }

        $payload = [
            'type' => $this->rootExporter->getType(),
            'version' => '2',
            'root' => $this->rootExporter->uuid(),
            'name' => $this->rootExporter->getName($this->rootExporter->model),
            'export' => $export,
        ];

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
            'name' => $manifest['name'],
        ]);
    }
}
