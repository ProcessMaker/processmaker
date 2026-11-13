<?php

declare(strict_types=1);

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Script;
use ProcessMaker\Services\ScriptExecutorResolver;

class DeployScript extends Controller
{
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

    public function __construct(private readonly ScriptExecutorResolver $executors)
    {
    }

    /**
     * Create or update a ProcessMaker script by title using PHP source.
     */
    public function deployScript(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $name = $request->input('name');
        $code = $request->input('code');

        $script = Script::where('title', $name)->first();
        $created = $script === null;

        if ($created) {
            $executor = $this->executors->resolveForNewScript();
            if ($executor === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'No script executor is available to create a script.',
                ], 422);
            }

            $script = new Script();
            $script->title = $name;
            $script->description = '';
            $script->language = $executor->language;
            $script->script_executor_id = $executor->id;
            $script->run_as_user_id = $request->user()->id;
            $script->timeout = self::DEFAULT_TIMEOUT;
            $script->retry_attempts = self::DEFAULT_RETRY_ATTEMPTS;
            $script->retry_wait_time = self::DEFAULT_RETRY_WAIT_TIME;
        }

        $script->code = $code;
        $script->save();
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
}
