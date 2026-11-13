<?php

declare(strict_types=1);

namespace ProcessMaker\Http\Controllers\Api;

use DOMDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use ProcessMaker\Exception\MissingBpmnReferenceException;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Process;
use ProcessMaker\Services\ProcessBpmnReferenceService;
use Throwable;

class DeployProcess extends Controller
{
    /**
     * A whitelist of attributes that should not be
     * sanitized by our SanitizeInput middleware.
     *
     * @var array
     */
    public $doNotSanitize = [
        'bpmn',
    ];

    public function __construct(
        private readonly ProcessBpmnReferenceService $references
    ) {
    }

    public function deployProcess(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'bpmn' => 'required|string',
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        return $this->saveDeployedProcess($request);
    }

    private function saveDeployedProcess(Request $request): JsonResponse
    {
        try {
            $bpmn = $this->references->resolveNamedReferences($request->input('bpmn'));
            $xmlErrors = $this->validateXml($bpmn);
            if ($xmlErrors !== []) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid BPMN XML.',
                    'errors' => ['bpmn' => $xmlErrors],
                ], 422);
            }

            [$process, $created] = $this->upsertProcessByName($request, $bpmn);

            return response()->json([
                'success' => true,
                'action' => $created ? 'created' : 'updated',
                'process_id' => $process->id,
                'name' => $process->name,
            ]);
        } catch (Throwable $exception) {
            return $this->deployProcessFailureResponse($exception);
        }
    }

    /**
     * @return array{0: Process, 1: bool}
     */
    private function upsertProcessByName(Request $request, string $bpmn): array
    {
        return DB::transaction(function () use ($request, $bpmn): array {
            $process = Process::where('name', $request->input('name'))->first();
            $created = $process === null;

            if ($created) {
                $process = new Process();
                $process->name = $request->input('name');
                $process->description = '';
                $process->status = 'ACTIVE';
                $process->user_id = $request->user()->id;
            }

            $process->bpmn = $bpmn;
            $process->saveOrFail();

            return [$process->refresh(), $created];
        });
    }

    private function deployProcessFailureResponse(Throwable $exception): JsonResponse
    {
        if ($exception instanceof MissingBpmnReferenceException) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        report($exception);

        return response()->json([
            'success' => false,
            'message' => 'The process could not be saved.',
            'error' => $exception->getMessage(),
        ], 422);
    }

    public function getProcessBpmn(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $process = Process::where('name', $request->input('name'))->first();
        if ($process === null) {
            return response()->json([
                'success' => false,
                'message' => 'Process not found.',
                'name' => $request->input('name'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'process_id' => $process->id,
            'name' => $process->name,
            'bpmn' => $this->references->restoreNamedReferences((string) $process->bpmn),
        ]);
    }

    /**
     * @return list<string>
     */
    private function validateXml(string $bpmn): array
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $document = new DOMDocument();
        $valid = $document->loadXML($bpmn, LIBXML_NONET);
        $errors = array_map(
            static fn (\LibXMLError $error): string => trim($error->message),
            libxml_get_errors()
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $valid ? [] : $errors;
    }
}
