<?php

namespace ProcessMaker\Http\Controllers\Api;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Exporters\ExporterBase;
use ProcessMaker\ImportExport\Exporters\ProcessExporter;
use ProcessMaker\ImportExport\Exporters\ScreenExporter;
use ProcessMaker\ImportExport\Exporters\ScriptExporter;
use ProcessMaker\ImportExport\Exporters\TemplateExporter;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessTemplates;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;

class ExportController extends Controller
{
    protected array $types = [
        'screen' => [Screen::class, ScreenExporter::class],
        'process' => [Process::class, ProcessExporter::class],
        'script' => [Script::class, ScriptExporter::class],
        'process_templates' => [ProcessTemplates::class, TemplateExporter::class],
    ];

    /**
     * Return only the manifest
     */
    public function manifest(string $type, int $id): JsonResponse
    {
        $model = $this->getModel($type)->findOrFail($id);

        $exporter = new Exporter(true);
        $exporter->export($model, $this->types[$type][1]);

        return response()->json($exporter->payload(true), 200);
    }

    /**
     * Download the JSON export file.
     */
    public function download(Request $request, string $type, int $id)
    {
        $model = $this->getModel($type)->findOrFail($id);
        $post = $request->json()->all();
        $options = (isset($post['options'])) ? new Options($post['options']) : new Options([]);
        $password = (isset($post['password']) ? $post['password'] : null);

        $exporter = new Exporter();
        $exporter->export($model, $this->types[$type][1], $options);

        $payload = $exporter->payload();

        $forcePasswordProtect = false;
        foreach ($payload['export'] as $asset) {
            if ($asset['force_password_protect']) {
                $forcePasswordProtect = true;
            }
        }

        if (!$password && $forcePasswordProtect) {
            return abort(400, 'Password protection required.');
        }

        $exported = $exporter->exportInfo($payload);

        if ($password) {
            $payload = $exporter->encrypt($password, $payload);
        }

        return response()->streamDownload(
            function () use ($payload) {
                echo json_encode($payload);
            },
            $payload['name'] . '.json',
            [
                'Content-type' => 'application/json',
                'export-info' => $exported,
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
