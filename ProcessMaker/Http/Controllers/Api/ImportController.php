<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;

class ImportController extends Controller
{
    /**
     * Returns the manifest and dependency tree.
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file',
            // 'password' => 'sometimes|string',
            // 'options' => 'array',
        ]);

        $payload = $request->file('file')->get();
        $options = new Options([]);
        $importer = new Importer(json_decode($payload, true), $options);

        return response()->json([
            'tree' => $importer->tree(),
            'manifest' => $importer->previewImport(),
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
