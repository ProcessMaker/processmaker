<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Events\EnvironmentVariablesCreated;
use ProcessMaker\Events\EnvironmentVariablesDeleted;
use ProcessMaker\Events\EnvironmentVariablesUpdated;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\EnvironmentVariables as EnvironmentVariableResource;
use ProcessMaker\Models\EnvironmentVariable;

class EnvironmentVariablesController extends Controller
{
    /**
     * A whitelist of attributes that should not be
     * sanitized by our SanitizeInput middleware.
     *
     * @var array
     */
    public $doNotSanitize = [
        'value',
    ];

    /**
     * Fetch a collection of variables based on paged request and filter if provided
     *
     * @param Request $request
     *
     * @return ResponseFactory|Response A list of matched users and paging data
     *
     * @OA\Get(
     *     path="/environment_variables",
     *     summary="Returns all environmentVariables that the user has access to. For security, values are not included.",
     *     operationId="getEnvironmentVariables",
     *     tags={"Environment Variables"},
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *     @OA\Parameter(ref="#/components/parameters/include"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of environmentVariables",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/EnvironmentVariable"),
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
        // Grab pagination data
        $perPage = $request->input('per_page', 10);
        // Filter
        $filter = $request->input('filter', null);
        $orderBy = $request->input('order_by', 'name');
        $orderDirection = $request->input('order_direction', 'asc');
        // Note, the current page is automatically handled by Laravel's pagination feature
        if ($filter) {
            $filter = '%' . $filter . '%';
            $environment_variables = EnvironmentVariable::where('name', 'like', $filter)
              ->orWhere('description', 'like', $filter)
              ->orderBy($orderBy, $orderDirection);
            $environment_variables = $environment_variables->paginate($perPage);
        } else {
            $environment_variables = EnvironmentVariable::orderBy($orderBy, $orderDirection)->paginate($perPage);
        }
        // Return fractal representation of paged data
        return new ApiCollection($environment_variables);
    }

    /**
     * Creates a new global Environment Variable in the system
     *
     *   @OA\Post(
     *     path="/environment_variables",
     *     summary="Create a new environment variable",
     *     operationId="createEnvironmentVariable",
     *     tags={"Environment Variables"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/EnvironmentVariableEditable")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/EnvironmentVariable")
     *     ),
     * )
     */
    public function store(Request $request)
    {
        $request->validate(EnvironmentVariable::rules(), EnvironmentVariable::messages());
        $environment_variable = EnvironmentVariable::create($request->all());
        // Register the Event
        EnvironmentVariablesCreated::dispatch($request->all());

        return new EnvironmentVariableResource($environment_variable);
    }

    /**
     * Return an environment variable instance
     * Using implicit model binding, will automatically return 404 if variable now found
     *
     * @OA\Get(
     *     path="/environment_variables/{environment_variable_id}",
     *     summary="Get an environment variable by id. For security, the value is not included.",
     *     operationId="getEnvironmentVariableById",
     *     tags={"Environment Variables"},
     *     @OA\Parameter(
     *         description="ID of environment_variables to return",
     *         in="path",
     *         name="environment_variable_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/EnvironmentVariable")
     *     ),
     * )
     */
    public function show(EnvironmentVariable $environment_variable)
    {
        return new EnvironmentVariableResource($environment_variable);
    }

    /**
     * Update an environment variable
     *
     * @OA\Put(
     *     path="/environment_variables/{environment_variable_id}",
     *     summary="Update an environment variable",
     *     operationId="updateEnvironmentVariable",
     *     tags={"Environment Variables"},
     *     @OA\Parameter(
     *         description="ID of environment variables to update",
     *         in="path",
     *         name="environment_variable_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/EnvironmentVariableEditable")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/EnvironmentVariable")
     *     ),
     * )
     */
    public function update(EnvironmentVariable $environment_variable, Request $request)
    {
        $fields = ['name', 'description'];
        if ($request->filled('value')) {
            $fields[] = 'value';
        }
        // Validate the request, passing in the existing variable to tweak unique rule on name
        $request->validate(EnvironmentVariable::rules($environment_variable));
        $original = $environment_variable->getOriginal();
        $environment_variable->fill($request->only($fields));
        $environment_variable->save();

        $changes = $environment_variable->getChanges();

        // Register the Event
        EnvironmentVariablesUpdated::dispatch($environment_variable, $changes, $original);

        return new EnvironmentVariableResource($environment_variable);
    }

    /**
     * @OA\Delete(
     *     path="/environment_variables/{environment_variable_id}",
     *     summary="Delete an environment variable",
     *     operationId="deleteEnvironmentVariable",
     *     tags={"Environment Variables"},
     *     @OA\Parameter(
     *         description="ID of environment_variables to return",
     *         in="path",
     *         name="environment_variable_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *     ),
     * )
     */
    public function destroy(EnvironmentVariable $environment_variable)
    {
        $environment_variable->delete();

        // Register the Event
        EnvironmentVariablesDeleted::dispatch($environment_variable);

        return response('', 200);
    }
}
