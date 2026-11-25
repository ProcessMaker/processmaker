<?php

declare(strict_types=1);

namespace ProcessMaker\Http\Controllers\Api;

use DOMDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ProcessMaker\Exception\MissingBpmnReferenceException;
use ProcessMaker\Http\Controllers\Api\Concerns\AppliesDeployVersionName;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Process;
use ProcessMaker\Services\DeployCategoryResolver;
use ProcessMaker\Services\DeployProcessAlternativeB;
use ProcessMaker\Services\ProcessBpmnReferenceService;
use Throwable;

class DeployProcess extends Controller
{
    use AppliesDeployVersionName;

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
        private readonly ProcessBpmnReferenceService $references,
        private readonly DeployCategoryResolver $categories,
        private readonly DeployProcessAlternativeB $alternativeB,
    ) {
    }

    public function deployProcess(Request $request): JsonResponse
    {
        $name = (string) $request->input('name');
        $isUpdate = Process::where('name', $name)->exists();
        // Alternative B writes drafts or B versions, not asset version history.
        $needsVersionName = $isUpdate && !$this->targetsAlternativeB($request);

        if ($unavailable = $this->ensureDeployVersionHistoryAvailable($needsVersionName)) {
            return $unavailable;
        }

        $validator = Validator::make(
            $request->all(),
            $this->deployRulesForUpdate([
                'bpmn' => 'required|string',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:65535',
                'process_category_name' => 'nullable|string|max:255',
                'alternative' => 'nullable|in:A,B',
                'publish' => 'nullable|boolean',
            ], $needsVersionName),
        );

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
            return $this->deployFromRequest($request);
        } catch (Throwable $exception) {
            return $this->deployProcessFailureResponse($exception);
        }
    }

    private function deployFromRequest(Request $request): JsonResponse
    {
        $bpmn = $this->references->resolveNamedReferences($request->input('bpmn'));
        $xmlErrors = $this->validateXml($bpmn);
        if ($xmlErrors !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid BPMN XML.',
                'errors' => ['bpmn' => $xmlErrors],
            ], 422);
        }

        if ($this->targetsAlternativeB($request)) {
            return $this->saveDeployedAlternativeB($request, $bpmn);
        }

        [$process, $created] = $this->upsertProcessByName($request, $bpmn);

        return response()->json([
            'success' => true,
            'action' => $created ? 'created' : 'updated',
            'process_id' => $process->id,
            'name' => $process->name,
        ]);
    }

    private function targetsAlternativeB(Request $request): bool
    {
        return $request->input('alternative') === 'B';
    }

    private function saveDeployedAlternativeB(Request $request, string $bpmn): JsonResponse
    {
        $process = Process::where('name', $request->input('name'))->first();
        $publish = $request->boolean('publish');
        $result = $this->alternativeB->deploy($process, $bpmn, $publish);

        return response()->json([
            'success' => true,
            'action' => $publish ? 'published' : 'draft',
            'alternative' => 'B',
            'process_id' => $process->id,
            'name' => $process->name,
            'process_version_id' => $result['version_id'],
        ]);
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
                $process->description = $this->nonEmptyDescription(
                    $request->input('description'),
                    (string) $request->input('name'),
                );
                $process->status = 'ACTIVE';
                $process->user_id = $request->user()->id;
                $categoryId = $this->categories->resolveProcessCategoryId(
                    $request->input('process_category_name')
                );
                if ($categoryId !== null) {
                    $process->process_category_id = $categoryId;
                }
            } elseif ($request->has('description')) {
                $process->description = $this->nonEmptyDescription(
                    $request->input('description'),
                    (string) $process->name,
                );
            }

            if (!$created && $request->has('process_category_name')) {
                $categoryId = $this->categories->resolveProcessCategoryId(
                    $request->input('process_category_name')
                );
                if ($categoryId !== null) {
                    $process->process_category_id = $categoryId;
                }
            }

            $process->bpmn = $bpmn;
            $publishedVersionIdBefore = $this->latestPublishedVersionId($process);
            $process->saveOrFail();

            $this->applyDeployVersionName($process, $request, !$created, $publishedVersionIdBefore);

            return [$process->refresh(), $created];
        });
    }

    private function deployProcessFailureResponse(Throwable $exception): JsonResponse
    {
        if ($exception instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => collect($exception->errors())->flatten()->first(),
                'errors' => $exception->errors(),
            ], 422);
        }

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

    private function nonEmptyDescription(mixed $description, string $fallbackName): string
    {
        if (is_string($description) && trim($description) !== '') {
            return trim($description);
        }

        return 'Process: ' . trim($fallbackName);
    }
}
