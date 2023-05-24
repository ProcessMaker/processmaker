<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ProcessMaker\Events\TemplateCreated;
use ProcessMaker\Exception\ImportPasswordException;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\ImportExport\ExportEncrypted;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessTemplates;

class ImportController extends Controller
{
    /**
     * Returns the manifest and dependency tree.
     */
    public function preview(Request $request, $version = null): JsonResponse
    {
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

    public function import(Request $request): JsonResponse
    {
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

        return response()->json(['processId' => $newProcessId], 200);
    }

    public function importTemplate(String $type, Request $request): JsonResponse
    {   
   
        $jsonData = $request->file('file')->get();
        $payload = json_decode($jsonData, true);
        //Call new event to Store new Templates on LOG
        event(new TemplateCreated($payload));
        
        $options = new Options(json_decode(file_get_contents(utf8_decode($request->file('options'))), true));
        $importer = new Importer($payload, $options);
        $manifest = $importer->doImport();

        return response()->json([], 200);
    }

    private function handlePasswordDecrypt(Request $request, array $payload)
    {
        if (isset($payload['encrypted']) && $payload['encrypted']) {
            $password = $request->input('password');
            if (!$password) {
                throw new ImportPasswordException('password required');
            }

            $payload = (new ExportEncrypted($password))->decrypt($payload);

            if ($payload['export'] === null) {
                throw new ImportPasswordException('incorrect password');
            }
        }

        return $payload;
    }
}
