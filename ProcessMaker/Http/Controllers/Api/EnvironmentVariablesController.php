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
   */
  public function show(EnvironmentVariable $environment_variable)
  {
      return new EnvironmentVariableResource($environment_variable);
  }
  /**
   * Update an environment variable
   */
  public function update(EnvironmentVariable $environment_variable, Request $request)
  {
      // Validate the request, passing in the existing variable to tweak unique rule on name
      $request->validate(EnvironmentVariable::rules($environment_variable));
      $environment_variable->fill($request->all());
      $environment_variable->save();
      return new EnvironmentVariableResource($environment_variable);
  }
  public function destroy(EnvironmentVariable $environment_variable)
  {
      $environment_variable->delete();
      return response('',200);
  }
}
