<?php

namespace ProcessMaker\Http\Controllers\Api;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ProcessMaker\Exception\ExportModelNotFoundException;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Exporters\ProcessExporter;
use ProcessMaker\ImportExport\Exporters\ScreenExporter;
use ProcessMaker\ImportExport\Exporters\ScriptExporter;
use ProcessMaker\ImportExport\Exporters\TemplateExporter;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessTemplates;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use ProcessMaker\PackageHelper;

class ExportController extends Controller
{
    const DATA_SOURCE_CLASS = 'ProcessMaker\Packages\Connectors\DataSources\Models\DataSource';

    const DATA_SOURCE_EXPORTER_CLASS = 'ProcessMaker\Packages\Connectors\DataSources\ImportExport\DataSourceExporter';

    const DECISION_TABLE_CLASS = 'ProcessMaker\Package\PackageDecisionEngine\Models\DecisionTable';

    const DECISION_TABLE_EXPORTER_CLASS = 'ProcessMaker\Package\PackageDecisionEngine\ImportExport\DecisionTableExporter';

    protected array $types = [
        'screen' => [Screen::class, ScreenExporter::class],
        'process' => [Process::class, ProcessExporter::class],
        'script' => [Script::class, ScriptExporter::class],
        'process_templates' => [ProcessTemplates::class, TemplateExporter::class],
    ];

    public function __construct()
    {
        if (PackageHelper::isPackageInstalled(self::DATA_SOURCE_CLASS)) {
            $this->types['data_source'] = [self::DATA_SOURCE_CLASS, self::DATA_SOURCE_EXPORTER_CLASS];
        }
        if (PackageHelper::isPackageInstalled(self::DECISION_TABLE_CLASS)) {
            $this->types['decision_table'] = [self::DECISION_TABLE_CLASS, self::DECISION_TABLE_EXPORTER_CLASS];
        }
    }

    /**
     * Return only the manifest
     */
    public function manifest(string $type, int $id): JsonResponse
    {
        $model = $this->getModel($type)->findOrFail($id);
        try {
            $exporter = new Exporter(true);
            $exporter->export($model, $this->types[$type][1]);

            return response()->json($exporter->payload(true), 200);
        } catch (ExportModelNotFoundException $error) {
            \Log::error(response()->json(['error' => $error->getMessage()], 400));
        }
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

        $filename = strtolower(str_replace(' ', '_', $payload['name'])) . '.json';

        return response()->streamDownload(
            function () use ($payload) {
                echo json_encode($payload);
            },
            $filename,
            [
                'Content-type' => 'application/json',
                'export-info' => $exported,
            ]
        );
    }

    public function getModel(string $type): Model
    {
        if (isset($this->types[$type])) {
            $modelClass = current($this->types[$type]);

            return new $modelClass;
        }
        throw new Exception("Type {$type} not found", 404);
    }
}
