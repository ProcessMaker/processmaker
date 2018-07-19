<?php

namespace ProcessMaker\Http\Controllers\Api\Requests;

use Auth;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Application;
use ProcessMaker\Transformers\ApplicationTransformer;

/**
 * API endpoint for returning Requests
 */
class RequestsController extends Controller
{
    /**
     * Returns the list of requests that the current user has created
     *
     * @param Request $request
     * @return array $result result of the query
     */

    public function index(Request $request)
    {
        $owner = Auth::user();
        $options = [
            'filter' => $request->input('filter', ''),
            'current_page' => $request->input('current_page', 1),
            'per_page' => $request->input('per_page', 10),
            'sort_by' => $request->input('sort_by', 'username'),
            'order_direction' => $request->input('order_direction', 'ASC'),
        ];
        $include = $request->input('include');

        $requests = Application::where('creator_user_id', $owner->id)
            ->with($include ? explode(',', $include) : [])
            ->paginate($options['per_page'])
            ->appends($options);

        // Return fractal representation of paged data
        return fractal($requests, new ApplicationTransformer())->respond();
    }
}
