<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use ProcessMaker\Events\ScriptExecutorCreated;
use ProcessMaker\Events\ScriptExecutorDeleted;
use ProcessMaker\Events\ScriptExecutorUpdated;
use ProcessMaker\Facades\Docker;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Jobs\BuildScriptExecutor;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptExecutor;

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
     *     @OA\Get(
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

        return new ApiCollection(ScriptExecutor::all());
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
     *     @OA\Post(
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
    public function store(Request $request)
    {
        $this->checkAuth($request);
        $request->validate(ScriptExecutor::rules());

        $scriptExecutor = ScriptExecutor::create(
            $request->only((new ScriptExecutor)->getFillable())
        );

        ScriptExecutorCreated::dispatch($scriptExecutor->getAttributes());

        BuildScriptExecutor::dispatch($scriptExecutor->id, $request->user()->id);

        return ['status'=>'started', 'id' => $scriptExecutor->id];
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
     *     @OA\Put(
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
    public function update(Request $request, ScriptExecutor $scriptExecutor)
    {
        $this->checkAuth($request);
        $request->validate(ScriptExecutor::rules());

        $original_values = $scriptExecutor->getAttributes();

        $scriptExecutor->update(
            $request->only($scriptExecutor->getFillable())
        );

        if (!empty($scriptExecutor->getChanges())) {
            ScriptExecutorUpdated::dispatch($scriptExecutor->id, $original_values, $scriptExecutor->getChanges());
        }

        BuildScriptExecutor::dispatch($scriptExecutor->id, $request->user()->id);

        return ['status'=>'started'];
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
     *     @OA\Delete(
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
    public function delete(Request $request, ScriptExecutor $scriptExecutor)
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
                throw ValidationException::withMessages(['delete' => _('Error removing image.') . " ${cmd} " . implode("\n", $out)]);
            }
        }

        ScriptExecutorDeleted::dispatch($scriptExecutor->getAttributes());

        ScriptExecutor::destroy($scriptExecutor->id);

        return ['status' => 'done'];
    }

    private function checkAuth($request)
    {
        if (!$request->user()->is_administrator) {
            throw new AuthorizationException;
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
     *     @OA\Post(
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
     *     @OA\Get(
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
            $languages[] = [
                'value' => $key,
                'text' => $config['name'],
                'initDockerfile' => ScriptExecutor::initDockerfile($key),
            ];
        }

        return ['languages' => $languages];
    }
}
