<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Events\TemplateCreated;
use ProcessMaker\Exception\ImportPasswordException;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Jobs\ImportV2;
use ProcessMaker\Models\ScreenTemplates;

class ImportController extends Controller
{
    /**
     * Returns the manifest and dependency tree.
     */
    public function preview(Request $request, $version = null): JsonResponse
    {
        if ($request->has('queue')) {
            Storage::putFileAs('import', $request->file('file'), 'payload.json');
            $hash = md5_file(Storage::path(ImportV2::FILE_PATH));
            $password = $request->input('password');

            ImportV2::dispatch($request->user()->id, $password, $hash, true);

            return response()->json([
                'queued' => true,
                'hash' => $hash,
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

    public function getImportManifest(Request $request)
    {
        $content = Storage::get(ImportV2::MANIFEST_PATH);

        return response($content, 200)->header('Content-Type', 'application/json');
    }

    public function import(Request $request): JsonResponse
    {
        if ($request->has('queue')) {
            Storage::put(ImportV2::OPTIONS_PATH, json_encode($request->get('options')));
            $password = $request->get('password');
            $hash = $request->get('hash');
            ImportV2::dispatch($request->user()->id, $password, $hash, false);

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

    public function importScreen(Request $request)
    {
        $jsonData = $request->file('file')->get();
        $payload = json_decode($jsonData, true);

        $postOptions = [];
        foreach ($payload['export'] as $key => $asset) {
            $postOptions[$key] = [
                'mode' => 'copy',
            ];
        }

        $options = new Options($postOptions);

        $importer = new Importer($payload, $options);
        $manifest = $importer->doImport();

        // // Call Event to store Template Changes in Log
        TemplateCreated::dispatch($payload);

        return $manifest;
    }

    private function handlePasswordDecrypt(Request $request, array $payload)
    {
        return Importer::handlePasswordDecrypt($payload, $request->input('password'));
    }
}
