<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\ImportExport\ExportEncrypted;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\Process;

class PasswordException extends \Exception
{
}

class ImportController extends Controller
{
    /**
     * Returns the manifest and dependency tree.
     */
    public function preview(Request $request): JsonResponse
    {
        $payload = json_decode($request->file('file')->get(), true);

        try {
            $payload = $this->handlePasswordDecrypt($request, $payload);
        } catch (PasswordException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
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
        $payload = json_decode($request->file('file')->get(), true);

        try {
            $payload = $this->handlePasswordDecrypt($request, $payload);
        } catch (PasswordException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
        $options = file_get_contents(utf8_decode($request->file('options')));
        $postOptions = json_decode($options, true);
        $options = new Options($postOptions);
        $importer = new Importer($payload, $options);
        $manifest = $importer->doImport();

        $rootLog = $manifest[$payload['root']]->log;
        $processId = $rootLog['newId'];

        return response()->json(['processId' => $processId], 200);
    }

    private function handlePasswordDecrypt(Request $request, array $payload)
    {
        if (isset($payload['encrypted']) && $payload['encrypted']) {
            $password = $request->input('password');
            if (!$password) {
                throw new PasswordException('password required');
            }

            $payload = (new ExportEncrypted($password))->decrypt($payload);

            if ($payload['export'] === null) {
                throw new PasswordException('incorrect password');
            }
        }

        return $payload;
    }
}
