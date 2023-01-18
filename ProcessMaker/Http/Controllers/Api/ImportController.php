<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\ImportExport\ExportEncrypted;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;

class ImportController extends Controller
{
    /**
     * Returns the manifest and dependency tree.
     */
    public function preview(Request $request): JsonResponse
    {
        $payload = json_decode($request->file('file')->get(), true);

        if (isset($payload['encrypted']) && $payload['encrypted']) {
            $password = $request->input('password');
            if (!$password) {
                return response()->json(['password_required' => true], 401);
            }
            $payload = (new ExportEncrypted($password))->decrypt($payload['export']);
        }

        $options = new Options([]);
        $importer = new Importer($payload, $options);

        $manifest = $importer->previewImport();

        return response()->json([
            'manifest' => $manifest,
            'rootUuid' => $payload['root'],
        ], 200);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file',
            // 'password' => 'sometimes|string',
            // 'options' => 'array',
        ]);

        $payload = $request->file('file')->get();
        $options = new Options([]);
        $importer = new Importer(json_decode($payload, true), $options);
        $importer->doImport();

        return response()->json([], 200);
    }
}
