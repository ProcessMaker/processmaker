<?php

namespace ProcessMaker\Http\Controllers\Api;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\EnvironmentVariables as EnvironmentVariableResource;

use Illuminate\Http\Request;

class EnvironmentVariablesController extends Controller
{
  /**
   * Fetch a collection of variables based on paged request and filter if provided
   *
   * @param Request $request
   *
   * @return ResponseFactory|Response A list of matched users and paging data
   *
   *      @OA\Get(
    *     path="/environment_variables",
    *     summary="Returns all environmentVariables that the user has access to",
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
    *                 @OA\Items(ref="#/components/schemas/environment_variables"),
    *             ),
    *             @OA\Property(
    *                 property="meta",
    *                 type="object",
    *                 allOf={@OA\Schema(ref="#/components/schemas/metadata")},
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
      if($filter) {
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
   *      * @OA\Get(
    *     path="/environment_variables/{environment_variables_id}",
    *     summary="Get single environment_variables by ID",
    *     operationId="getEnvironmentVariablesById",
    *     tags={"Environment Variables"},
    *     @OA\Parameter(
    *         description="ID of environment_variables to return",
    *         in="path",
    *         name="environment_variables_id",
    *         required=true,
    *         @OA\Schema(
    *           type="string",
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Successfully found the process",
    *         @OA\JsonContent(ref="#/components/schemas/environment_variables")
    *     ),
    * )
   */
  public function store(Request $request)
  {
      $request->validate(EnvironmentVariable::rules());
      $environment_variable = EnvironmentVariable::create($request->all());

      return new EnvironmentVariableResource($environment_variable);

  }
  /**
   * Return an environment variable instance
   * Using implicit model binding, will automatically return 404 if variable now found
   *
   *      @OA\Post(
    *     path="/environment_variables",
    *     summary="Save a new environment_variables",
    *     operationId="createEnvironmentVariables",
    *     tags={"Environment Variables"},
    *     @OA\RequestBody(
    *       required=true,
    *       @OA\JsonContent(ref="#/components/schemas/environment_variablesEditable")
    *     ),
    *     @OA\Response(
    *         response=201,
    *         description="success",
    *         @OA\JsonContent(ref="#/components/schemas/environment_variables")
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
   *      @OA\Put(
    *     path="/environment_variables/{environment_variables_id}",
    *     summary="Update a environment_variables",
    *     operationId="updateEnvironmentVariables",
    *     tags={"Environment Variables"},
    *     @OA\Parameter(
    *         description="ID of environment_variables to return",
    *         in="path",
    *         name="environment_variables_id",
    *         required=true,
    *         @OA\Schema(
    *           type="string",
    *         )
    *     ),
    *     @OA\RequestBody(
    *       required=true,
    *       @OA\JsonContent(ref="#/components/schemas/environment_variablesEditable")
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="success",
    *         @OA\JsonContent(ref="#/components/schemas/environment_variables")
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
      $environment_variable->fill($request->only($fields));
      $environment_variable->save();
      return new EnvironmentVariableResource($environment_variable);
  }

  /**
   *      @OA\Delete(
    *     path="/environment_variables/{environment_variables_id}",
    *     summary="Delete a environment_variables",
    *     operationId="deleteEnvironmentVariables",
    *     tags={"Environment Variables"},
    *     @OA\Parameter(
    *         description="ID of environment_variables to return",
    *         in="path",
    *         name="environment_variables_id",
    *         required=true,
    *         @OA\Schema(
    *           type="string",
    *         )
    *     ),
    *     @OA\Response(
    *         response=204,
    *         description="success",
    *         @OA\JsonContent(ref="#/components/schemas/environment_variables")
    *     ),
    * )
   */
  public function destroy(EnvironmentVariable $environment_variable)
  {
      $environment_variable->delete();
      return response('',200);
  }
}
