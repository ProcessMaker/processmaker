<?php

declare(strict_types=1);

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use ProcessMaker\Http\Controllers\Api\Concerns\AppliesDeployVersionName;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\User;
use ProcessMaker\Services\DeployCategoryResolver;
use ProcessMaker\Services\ScriptExecutorResolver;

class DeployScript extends Controller
{
    use AppliesDeployVersionName;

    /**
     * A whitelist of attributes that should not be
     * sanitized by our SanitizeInput middleware.
     *
     * @var array
     */
    public $doNotSanitize = [
        'code',
    ];

    private const DEFAULT_TIMEOUT = 60;

    private const DEFAULT_RETRY_ATTEMPTS = 0;

    private const DEFAULT_RETRY_WAIT_TIME = 5;

    public function __construct(
        private readonly ScriptExecutorResolver $executors,
        private readonly DeployCategoryResolver $categories,
    ) {
    }

    /**
     * Create or update a ProcessMaker script by title using PHP source.
     */
    public function deployScript(Request $request): JsonResponse
    {
        $name = (string) $request->input('name');
        $isUpdate = Script::where('title', $name)->exists();

        $rejection = $this->rejectDeployRequest($request, $name, $isUpdate);

        return $rejection ?? $this->persistDeployedScript($request, $name);
    }

    /**
     * Return the response that stops a deploy (version history unavailable,
     * validation failure, or forbidden executor binding), or null to proceed.
     */
    private function rejectDeployRequest(Request $request, string $name, bool $isUpdate): ?JsonResponse
    {
        if ($unavailable = $this->ensureDeployVersionHistoryAvailable($isUpdate)) {
            return $unavailable;
        }

        $validator = Validator::make(
            $request->all(),
            $this->deployRulesForUpdate([
                'code' => 'required|string',
                'name' => 'required|string',
                'description' => 'nullable|string|max:65535',
                'script_category_name' => 'nullable|string|max:255',
                'script_executor_id' => 'nullable|integer',
                'script_executor_title' => 'nullable|string|max:255',
            ], $isUpdate),
        );
        $validator->after(fn ($validator) => $this->validateRequestedExecutor($request, $validator));

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        return $this->denyUnauthorizedExecutorBinding($request, $name);
    }

    /**
     * Binding a script to an executor needs the same rights as the regular script
     * routes: edit-scripts, and an administrator for any executor other than the
     * default one (or any custom executor). Requests that name no executor, and
     * requests that keep the executor a script already has, are not restricted.
     */
    private function denyUnauthorizedExecutorBinding(Request $request, string $name): ?JsonResponse
    {
        $requested = $this->requestedExecutor($request);
        $message = $requested === null
            ? null
            : $this->executorBindingDenialMessage($request->user(), $requested, $name);

        if ($message === null) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => $message,
        ], 403);
    }

    /**
     * Return why the user may not bind the script to the requested executor,
     * or null when the binding is allowed.
     */
    private function executorBindingDenialMessage(User $user, ScriptExecutor $requested, string $name): ?string
    {
        if ($user->is_administrator || $this->keepsCurrentExecutor($name, $requested)) {
            return null;
        }

        if (!$user->can('edit-scripts')) {
            return 'You are not allowed to choose a script executor.';
        }

        return $this->executors->isDefaultForNonAdmins($requested)
            ? null
            : 'Only administrators can use this script executor.';
    }

    private function keepsCurrentExecutor(string $name, ScriptExecutor $requested): bool
    {
        $current = Script::where('title', $name)->value('script_executor_id');

        return $current !== null && (int) $current === $requested->id;
    }

    private function requestedExecutor(Request $request): ?ScriptExecutor
    {
        $id = $request->input('script_executor_id');

        return $this->executors->findRequested(
            $id === null || $id === '' ? null : (int) $id,
            $request->input('script_executor_title'),
        );
    }

    private function validateRequestedExecutor(Request $request, \Illuminate\Validation\Validator $validator): void
    {
        $field = $request->filled('script_executor_id') ? 'script_executor_id' : 'script_executor_title';
        if (!$request->filled($field)) {
            return;
        }

        $executor = $this->requestedExecutor($request);
        if ($executor === null) {
            $validator->errors()->add($field, 'The selected script executor does not exist.');

            return;
        }
        if ($executor->language !== ScriptExecutorResolver::DEPLOY_LANGUAGE) {
            $validator->errors()->add($field, sprintf(
                'Script executor "%s" runs %s; deploy-script needs a %s executor.',
                $executor->title,
                $executor->language,
                ScriptExecutorResolver::DEPLOY_LANGUAGE,
            ));
        }
    }

    private function persistDeployedScript(Request $request, string $name): JsonResponse
    {
        $code = $request->input('code');
        $script = Script::where('title', $name)->first();
        $created = $script === null;
        $requestedExecutor = $this->requestedExecutor($request);

        if ($created) {
            $executor = $requestedExecutor ?? $this->executors->resolveForNewScript();
            if ($executor === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'No script executor is available to create a script.',
                ], 422);
            }

            $script = new Script();
            $script->title = $name;
            $script->description = $this->deployScriptDescription($request, $name);
            $script->language = $executor->language;
            $script->script_executor_id = $executor->id;
            $script->run_as_user_id = $request->user()->id;
            $script->timeout = self::DEFAULT_TIMEOUT;
            $script->retry_attempts = self::DEFAULT_RETRY_ATTEMPTS;
            $script->retry_wait_time = self::DEFAULT_RETRY_WAIT_TIME;
        }

        if (!$created && $requestedExecutor !== null) {
            $script->script_executor_id = $requestedExecutor->id;
            $script->language = $requestedExecutor->language;
        }

        if (!$created && $request->has('description')) {
            $script->description = $this->deployScriptDescription($request, $name);
        }

        if ($created || $request->has('script_category_name')) {
            $categoryId = $this->categories->resolveScriptCategoryId(
                $request->input('script_category_name')
            );
            if ($categoryId !== null) {
                $script->script_category_id = (string) $categoryId;
            }
        }

        $script->code = $code;
        $publishedVersionIdBefore = $this->latestPublishedVersionId($script);
        $script->save();
        $this->applyDeployVersionName($script, $request, !$created, $publishedVersionIdBefore);
        $script->refresh();

        return response()->json([
            'success' => true,
            'action' => $created ? 'created' : 'updated',
            'script_id' => $script->id,
            'name' => $script->title,
            'message' => $created
                ? 'Script created from PHP.'
                : 'Script updated from PHP.',
        ]);
    }

    /**
     * Return the published script PHP source, looked up by title.
     *
     * @return \Illuminate\Http\Response|JsonResponse
     */
    public function getScriptPhp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $name = $request->input('name');
        $script = Script::where('title', $name)->first();
        $scriptVersion = $script?->getPublishedVersion([]);
        if ($script === null || $scriptVersion === null) {
            $payload = [
                'success' => false,
                'message' => $script === null
                    ? 'Script not found'
                    : 'Script does not have a published version',
                'name' => $name,
            ];
            if ($script !== null) {
                $payload['script_id'] = $script->id;
            }

            return response()->json($payload, 404);
        }

        return response((string) ($scriptVersion->code ?? ''), 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('X-Script-Id', (string) $script->id)
            ->header('X-Script-Name', $script->title);
    }

    private function deployScriptDescription(Request $request, string $title): string
    {
        $description = $request->input('description');
        if (is_string($description) && trim($description) !== '') {
            return trim($description);
        }

        return 'Script: ' . trim($title);
    }
}
