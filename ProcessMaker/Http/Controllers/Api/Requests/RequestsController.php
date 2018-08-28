<?php

namespace ProcessMaker\Http\Controllers\Api\Requests;

use Auth;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ProcessCategory;
use ProcessMaker\Model\User;
use ProcessMaker\Transformers\ApplicationTransformer;
use ProcessMaker\Transformers\ProcessTransformer;

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
            'status' => $request->input('status', Application::STATUS_TO_DO),
        ];
        $include = $request->input('include');

        $requests = Application::where('creator_user_id', $owner->id)
            ->where('APP_STATUS', $options['status'])
            ->with($include ? explode(',', $include) : [])
            ->paginate($options['per_page'])
            ->appends($options);

        // Return fractal representation of paged data
        return fractal($requests, new ApplicationTransformer())->respond();
    }

    /**
     * Returns the list of processes that can be started by the connected user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserStartProcesses(Request $request) {
        $options = [
            'filter' => $request->input('filter', ''),
            'current_page' => $request->input('current_page', 1),
            'per_page' => $request->input('per_page', 10),
            'sort_by' => $request->input('order_by', 'name'),
            'sort_order' => $request->input('order_direction', 'ASC'),
        ];

        $sortingField = [
            'status' => 'status',
            'user' => 'user_id',
            'category' => 'process_category_id',
            'due_date' => 'updated_at',
            'name' => 'name',
            'description' => 'description'
        ];

        $query = Process::with(['category', 'user'])
                ->select(
                'processes.*',
                \DB::raw('(SELECT name from process_categories where processes.process_category_id = process_categories.id) as categoryName'));

        $query->where(function ($query) {
            $query->where('status', '=', 'ACTIVE');
        });

        if (!empty($options['filter'])) {
            // We want to search off of name and description and category name
            // Cannot join on table because of Eloquent's lack of specific table column names in generated SQL
            // See: https://github.com/laravel/ideas/issues/347
            $filter = '%' . $options['filter'] . '%';
            $category = new ProcessCategory();
            $user = new User();
            $query->where(function ($query) use ($filter, $category, $user) {
                $query->Where('name', 'like', $filter)
                    ->orWhere('description', 'like', $filter)
                    ->orWhere('status', 'like', $filter)
                    ->orWhere(function ($q) use ($filter, $category) {
                        $q->whereHas('category', function ($query) use ($filter, $category) {
                            $query->where($category->getTable() . '.name', 'like', $filter);
                        });
                    })
                    ->orWhere(function ($q) use ($filter, $user) {
                        $q->whereHas('user', function ($query) use ($filter, $user) {
                            $query->where($user->getTable() . '.firstname', 'like', $filter)
                                ->where($user->getTable() . '.lastname', 'like', $filter);
                        });
                    });
            });
        }

        $sortColumn = 'name';
        $sortDirection = $options['sort_order'];

        if (empty($request->input('order_by'))) {
            $query->orderBy('categoryName', 'asc');
            $sortDirection = 'ASC';
        }

        if (isset($sortingField[$options['sort_by']])) {
            $sortColumn = $sortingField[$options['sort_by']];
        }

        $query->orderBy($sortColumn, $sortDirection);

        $processes = $query->paginate($options['per_page'])
            ->appends($options);

        return fractal($processes, new ProcessTransformer())->respond();
    }
}
