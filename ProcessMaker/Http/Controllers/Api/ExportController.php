<?php

namespace ProcessMaker\Http\Controllers\Api;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Requests\ImportExport\ExportRequest;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Exporters\ProcessExporter;
use ProcessMaker\ImportExport\Exporters\ScreenExporter;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;

class ExportController extends Controller
{
    protected array $types = [
        'screen' => [Screen::class, ScreenExporter::class],
        'process' => [Process::class, ProcessExporter::class],
    ];

    /**
     * Get the dependency tree and manifest to use in the frontend when exporting.
     */
    public function tree(string $type, int $id): JsonResponse
    {
        $model = $this->getModel($type)->findOrFail($id);

        $exporter = new Exporter();
        $exporter->export($model, last($this->types[$type]));

        return response()->json([
            'tree' => $exporter->tree(),
            'manifest' => $exporter->payload(),
        ], 200);
    }

    /**
     * Download the JSON export file.
     */
    public function download(ExportRequest $request, string $type, int $id)
    {
        $model = $this->getModel($type)->findOrFail($id);

        $exporter = new Exporter();
        $exporter->export($model, last($this->types[$type]));

        $export = $exporter->payload($request->input('password'));
        $fileName = 'export.json';

        return response()->streamDownload(
            function () use ($export) {
                echo json_encode($export);
            },
            $fileName,
            [
                'Content-type' => 'application/json',
            ]
        );
    }

    private function getModel(string $type): Model
    {
        if (isset($this->types[$type])) {
            $modelClass = current($this->types[$type]);

            return new $modelClass;
        }
        throw new Exception("Type {$type} not found", 404);
    }
}
