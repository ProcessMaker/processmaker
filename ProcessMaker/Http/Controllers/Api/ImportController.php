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
     * @param Request $request
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file',
            // 'password' => 'sometimes|string',
        ]);

        $payload = $request->file('file')->get();
        $options = new Options([]);
        $importer = new Importer(json_decode($payload, true), $options);

        return response()->json([
            'tree' => $importer->tree(),
            'manifest' => $importer->previewImport(),
        ], 200);
    }
}
