<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use ProcessMaker\Enums\ScriptExecutorType;
use ProcessMaker\Events\ScriptExecutorCreated;
use ProcessMaker\Events\ScriptExecutorDeleted;
use ProcessMaker\Events\ScriptExecutorUpdated;
use ProcessMaker\Facades\Docker;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Jobs\BuildScriptExecutor;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Services\ScriptMicroserviceService;

class ScriptExecutorController extends Controller
{
    /**
     * Get a list of script executors.
     *
     * @param Request $request
     *
     * @return ResponseFactory|Response
     *
     *
     * @OA\Get(
     *      path="/script-executors",
     *      summary="Returns all script executors that the user has access to",
     *      operationId="getScriptExecutors",
     *      tags={"Rebuild Script Executors"},
     *      @OA\Parameter(ref="#/components/parameters/filter"),
     *      @OA\Parameter(ref="#/components/parameters/order_by"),
     *      @OA\Parameter(ref="#/components/parameters/order_direction"),
     *      @OA\Parameter(ref="#/components/parameters/per_page"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of script executors",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/scriptExecutors"),
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Schema(ref="#/components/schemas/metadata"),
     *             ),
     *         ),
     *     ),
     * )
     */
    public function index(Request $request)
    {
        $this->checkAuth($request);

        $query = ScriptExecutor::nonSystem();

        if ($request->has('order_by')) {
            $order_by = $request->input('order_by');
            $order_direction = $request->input('order_direction', 'ASC');
            $query->orderBy($order_by, $order_direction);
        }

        return new ApiCollection($query->get());
    }

    /**
     * Create a script executor
     *
     * @param Request $request
     * @param ScriptExecutor $scriptExecutor
     *
     * @return ResponseFactory|Response
     *
     *
     * @OA\Post(
     *      path="/script-executors",
     *      summary="Create a script executor",
     *      operationId="createScriptExecutor",
     *      tags={"Rebuild Script Executors"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/scriptExecutorsEditable")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         content={
     *           @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                @OA\Property(
     *                   property="status",
     *                   type="string"
     *                ),
     *                @OA\Property(
     *                   property="id",
     *                   type="string"
     *                ),
     *              ),
     *            ),
     *         }
     *     ),
     * )
     */
    public function store(Request $request, ScriptMicroserviceService $service)
    {
        $this->checkAuth($request);
        $request->validate(ScriptExecutor::rules());

        $scriptExecutor = ScriptExecutor::create(
            $request->only((new ScriptExecutor())->getFillable())
        );

        if (!config('script-runner-microservice.enabled')) {
            ScriptExecutorCreated::dispatch($scriptExecutor->getAttributes());
            BuildScriptExecutor::dispatch($scriptExecutor->id, $request->user()->id);
        } else {
            try {
                $service->createCustomExecutor($scriptExecutor);
            } catch (RequestException $e) {
                // The remote executor was rejected, so keeping the local record would leave
                // an executor that can never be built.
                $scriptExecutor->delete();

                $this->throwMicroserviceError($e);
            }
        }

        return ['status' => 'started', 'uuid' => $scriptExecutor->uuid, 'id' => $scriptExecutor->id];
    }

    /**
     * Update and rebuild the script executor
     *
     * @param Request $request
     * @param ScriptExecutor $scriptExecutor
     *
     * @return ResponseFactory|Response
     *
     *
     * @OA\Put(
     *      path="/script-executors/{script_executor}",
     *      summary="Update script executor",
     *      operationId="updateScriptExecutor",
     *      tags={"Rebuild Script Executors"},
     *      @OA\Parameter(
     *         description="ID of script executor to return",
     *         in="path",
     *         name="script_executor",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *
     *
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/scriptExecutorsEditable")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         content={
     *           @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                @OA\Property(
     *                   property="status",
     *                   type="string"
     *                ),
     *              ),
     *            ),
     *         }
     *     ),
     * )
     * )
     */
    public function update(Request $request, ScriptExecutor $scriptExecutor, ScriptMicroserviceService $service)
    {
        $this->checkAuth($request);
        $request->validate(ScriptExecutor::rules());

        $original = $scriptExecutor->getAttributes();

        $scriptExecutor->update(
            $request->only($scriptExecutor->getFillable())
        );

        if (config('script-runner-microservice.enabled') && $scriptExecutor->type?->isCustomOrRealtime()) {
            try {
                $service->updateCustomExecutor($scriptExecutor);
            } catch (RequestException $e) {
                $this->throwMicroserviceError($e);
            }
        } else {
            if (!empty($scriptExecutor->getChanges())) {
                ScriptExecutorUpdated::dispatch($scriptExecutor->id, $original, $scriptExecutor->getChanges());
            }
            BuildScriptExecutor::dispatch($scriptExecutor->id, $request->user()->id);
        }

        return ['status' => 'started', 'uuid' => $scriptExecutor->uuid];
    }

    /**
     * Delete a script executor
     *
     * @param Request $request
     * @param ScriptExecutor $scriptExecutor
     *
     * @return ResponseFactory|Response
     *
     *
     * @OA\Delete(
     *      path="/script-executors/{script_executor}",
     *      summary="Delete a script executor",
     *      operationId="deleteScriptExecutor",
     *      tags={"Rebuild Script Executors"},
     *      @OA\Parameter(
     *         description="ID of script executor to return",
     *         in="path",
     *         name="script_executor",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *      @OA\Response(
     *         response=200,
     *         description="success",
     *         content={
     *           @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                @OA\Property(
     *                   property="status",
     *                   type="string"
     *                )
     *              ),
     *            ),
     *         }
     *     ),
     * )
     */
    public function delete(Request $request, ScriptExecutor $scriptExecutor, ScriptMicroserviceService $service)
    {
        if ($scriptExecutor->scripts()->count() > 0) {
            throw ValidationException::withMessages(['delete' => __('Can not delete executor when it is assigned to scripts.')]);
        }

        if (ScriptExecutor::where('language', $scriptExecutor->language)->count() === 1) {
            throw ValidationException::withMessages(['delete' => __('Can not delete the only executor for this language.')]);
        }

        $cmd = Docker::command() . ' images -q ' . $scriptExecutor->dockerImageName();
        exec($cmd, $out, $return);
        if (count($out) > 0) {
            $cmd = Docker::command() . ' rmi ' . $scriptExecutor->dockerImageName();
            exec($cmd, $out, $return);

            if ($return !== 0) {
                throw ValidationException::withMessages(['delete' => _('Error removing image.') . " {$cmd} " . implode("\n", $out)]);
            }
        }

        $scriptExecutorUUID = $scriptExecutor->uuid;

        ScriptExecutor::destroy($scriptExecutor->id);

        if (!config('script-runner-microservice.enabled')) {
            ScriptExecutorDeleted::dispatch($scriptExecutor->getAttributes());
        } else {
            $service->deleteCustomExecutor($scriptExecutorUUID);
        }

        return ['status' => 'done'];
    }

    /**
     * Report a script microservice rejection on the form, since the build itself
     * is only reported over the websocket channel.
     *
     * @param RequestException $e Failed script microservice response
     *
     * @return never
     *
     * @throws ValidationException|RequestException
     */
    private function throwMicroserviceError(RequestException $e): never
    {
        if (!$e->response->clientError()) {
            throw $e;
        }

        $detail = $e->response->json('detail');
        $message = is_string($detail) && $detail !== '' ? $detail : $e->getMessage();

        throw ValidationException::withMessages(['language' => [$message]]);
    }

    private function checkAuth($request)
    {
        if (!config('app.custom_executors')) {
            abort(404);
        }

        if (!$request->user()->is_administrator) {
            throw new AuthorizationException();
        }
    }

    /**
     * Cancel a script executor
     *
     * @param Request $request
     *
     * @return ResponseFactory|Response
     *
     *
     * @OA\Post(
     *      path="/script-executors/cancel",
     *      summary="Cancel a script executor",
     *      operationId="cancelScriptExecutor",
     *      tags={"Rebuild Script Executors"},
     *      @OA\RequestBody(
     *        required=true,
     *        @OA\JsonContent(
     *          @OA\Property(property="pidFile", type="string"),
     *        )
     *      ),
     *      @OA\Response(
     *         response=200,
     *         description="success",
     *         content={
     *           @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                @OA\Property(
     *                   property="status",
     *                   type="string"
     *                ),
     *                @OA\Property(
     *                   property="id",
     *                   type="string"
     *                ),
     *              ),
     *            ),
     *         }
     *     ),
     * )
     */
    public function cancel(Request $request)
    {
        $pidFile = $request->input('pidFile');
        $pid = file_get_contents($pidFile);
        exec("kill -9 $pid");

        return ['status' => 'canceled', 'pid' => $pid];
    }

    /**
     * Get a list of available languages.
     *
     * @return ResponseFactory|Response
     *
     *
     * @OA\Get(
     *      path="/script-executors/available-languages",
     *      summary="Returns all available languages",
     *      operationId="getAvailableLanguages",
     *      tags={"Rebuild Script Executors"},
     *      @OA\Parameter(ref="#/components/parameters/filter"),
     *      @OA\Parameter(ref="#/components/parameters/order_by"),
     *      @OA\Parameter(ref="#/components/parameters/order_direction"),
     *      @OA\Parameter(ref="#/components/parameters/per_page"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of available languages",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/availableLanguages"),
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Schema(ref="#/components/schemas/metadata"),
     *             ),
     *         ),
     *     ),
     * )
     */
    public function availableLanguages()
    {
        $languages = [];
        foreach (Script::scriptFormats() as $key => $config) {
            // ToDo remove $key === 'php-nayra' validation when php-nayra include in deprecatedLanguages
            if (in_array($key, Script::deprecatedLanguages) || $key === 'php-nayra') {
                continue;
            }
            if (!array_key_exists('system', $config) || (array_key_exists('system', $config) && !$config['system'])) {
                $languages[] = [
                    'value' => $key,
                    'text' => $config['name'],
                    'language' => $key,
                    'realtime' => false,
                    'initDockerfile' => ScriptExecutor::initDockerfile($key),
                    'configExample' => '',
                ];
            }
        }

        foreach (['php', 'python', 'javascript'] as $language) {
            $dockerfilePath = resource_path("script-executors/realtime/{$language}.Dockerfile");
            if (!file_exists($dockerfilePath)) {
                continue;
            }
            $label = $language === 'javascript' ? 'nodejs (realtime)' : "{$language} (realtime)";
            $languages[] = [
                'value' => "{$language}-realtime",
                'text' => $label,
                'language' => $language,
                'realtime' => true,
                'initDockerfile' => '',
                'configExample' => file_get_contents($dockerfilePath),
            ];
        }

        return ['languages' => $languages];
    }
}
