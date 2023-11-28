<?php

namespace ProcessMaker\Http\Controllers\Api;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ProcessMaker\Enums\ExporterMap;
use ProcessMaker\Exception\ExportModelNotFoundException;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Options;

class ExportController extends Controller
{
    /**
     * Return only the manifest
     */
    public function manifest(string $type, int $id): JsonResponse
    {
        $model = $this->getModel($type)->findOrFail($id);
        try {
            $exporter = new Exporter(true);
            $exporter->export($model, ExporterMap::getExporterClass($type));

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
        $exporter->export($model, ExporterMap::getExporterClass($type), $options);

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
        $modelClass = ExporterMap::getModelClass($type);
        if ($modelClass) {
            return new $modelClass;
        }
        throw new Exception("Type {$type} not found", 404);
    }

    /**
     * Get asset manifest.
     *
     * @param string $type
     *
     * @param Request $request
     *
     * @return array
     */
    public function getManifest(string $type, int $id) : array
    {
        $response = (new self)->manifest($type, $id);

        return json_decode($response->getContent(), true);
    }
}
