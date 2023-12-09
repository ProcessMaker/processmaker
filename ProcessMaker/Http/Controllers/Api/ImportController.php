<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Events\TemplateCreated;
use ProcessMaker\Exception\ImportPasswordException;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\ImportExport\ExportEncrypted;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Jobs\ImportV2;

class ImportController extends Controller
{
    /**
     * Returns the manifest and dependency tree.
     */
    public function preview(Request $request, $version = null): JsonResponse
    {
        if ($request->has('queue')) {
            $filePath = Storage::putFile('import', $request->file('file'));
            $password = $request->input('password');

            ImportV2::dispatch($request->user()->id, $filePath, null, $password, true);

            return response()->json([
                'queued' => true,
                'filePath' => $filePath,
            ], 200);
        }

        $payload = json_decode($request->file('file')->get(), true);

        try {
            $payload = $this->handlePasswordDecrypt($request, $payload);
        } catch (ImportPasswordException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        $options = new Options([]);
        $importer = new Importer($payload, $options);

        $manifest = $importer->previewImport();

        return response()->json([
            'manifest' => $manifest,
            'rootUuid' => $payload['root'],
            'processVersion' => (int) $version,
        ], 200);
    }

    public function getImportManifest($request)
    {
        $path = $request->get('path');
        $content = Storage::get($path);
        if (str_starts_with($path, 'import/') && $content) {
            return response($content, 200)->header('Content-Type', 'application/json');
        }

        return response(null, 404);
    }

    public function import(Request $request): JsonResponse
    {
        if ($request->has('queue')) {
            $filePath = $request->get('filePath');
            $optionsPath = Storage::putFile('import', $request->file('options'));
            $password = $request->get('password');
            ImportV2::dispatch($request->user()->id, $filePath, $optionsPath, $password, false);

            return response()->json(['queued' => true], 200);
        }

        $jsonData = $request->file('file')->get();
        $payload = json_decode($jsonData, true);

        try {
            $payload = $this->handlePasswordDecrypt($request, $payload);
        } catch (ImportPasswordException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        $options = new Options(json_decode(file_get_contents(utf8_decode($request->file('options'))), true));
        $importer = new Importer($payload, $options);
        $manifest = $importer->doImport();

        $newProcessId = $manifest[$payload['root']]->log['newId'];

        return response()->json(['processId' => $newProcessId, 'message' => Importer::getMessages()], 200);
    }

    public function importTemplate(String $type, Request $request): JsonResponse
    {
        $jsonData = $request->file('file')->get();
        $payload = json_decode($jsonData, true);

        $options = new Options(json_decode(file_get_contents(utf8_decode($request->file('options'))), true));
        $importer = new Importer($payload, $options);
        $manifest = $importer->doImport();

        // Call Event to store Template Changes in Log
        TemplateCreated::dispatch($payload);

        return response()->json([], 200);
    }

    private function handlePasswordDecrypt(Request $request, array $payload)
    {
        return Importer::handlePasswordDecrypt($payload, $request->input('password'));
    }
}
