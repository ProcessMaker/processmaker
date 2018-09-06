<?php

namespace ProcessMaker\Http\Controllers\Api\Administration;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Transformers\EnvironmentVariableTransformer;

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
            $variables = EnvironmentVariable::where('name', 'like', $filter)
                ->orWhere('description', 'like', $filter)
                ->orderBy($orderBy, $orderDirection);
            $variables = $variables->paginate($perPage);
        } else {
            $variables = EnvironmentVariable::orderBy($orderBy, $orderDirection)->paginate($perPage);
        }
        // Return fractal representation of paged data
        return fractal($variables, new EnvironmentVariableTransformer())->respond();
    }


    /**
     * Creates a new global Environment Variable in the system
     */
    public function create(Request $request)
    {
        $request->validate(EnvironmentVariable::rules());

        $variable = EnvironmentVariable::create($request->all());

        return fractal($variable, new EnvironmentVariableTransformer())->respond(201);
    }

    /**
     * Return an environment variable instance
     * Using implicit model binding, will automatically return 404 if variable now found
     */
    public function get(EnvironmentVariable $variable)
    {
        return fractal($variable, new EnvironmentVariableTransformer())->respond();

    }

    /**
     * Update an environment variable
     */
    public function update(EnvironmentVariable $variable, Request $request)
    {
        // Validate the request, passing in the existing variable to tweak unique rule on name
        $request->validate(EnvironmentVariable::rules($variable));
        $variable->fill($request->all());
        $variable->save();
        return fractal($variable, new EnvironmentVariableTransformer())->respond();
    }

    public function delete(EnvironmentVariable $variable)
    {
        $variable->delete();
        return response('',200);
    }
}
