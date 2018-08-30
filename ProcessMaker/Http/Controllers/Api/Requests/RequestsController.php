<?php

namespace ProcessMaker\Http\Controllers\Api\Requests;

use Auth;
use Carbon\Carbon;
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

        $delay = $request->query('delay');
        $include = $request->input('include')
                    ? explode(',', $request->input('include'))
                    : [];

        $query = Application::with($include)
                    ->where('APP_STATUS', $options['status'])
                    ->where('creator_user_id', $owner->id);

        // if there is a filter by delay type (at risk, overdue, etc.) delegations are filtered
        if ($delay) {
            $query->with(['delegations' => function($q) use($delay) {
                $this->addDelayConditionsToQuery($q, $delay);
            }]);
            $query->whereHas('delegations', function($q) use($delay) {
                $this->addDelayConditionsToQuery($q, $delay);
            });
        }

        $requests = $query->paginate($options['per_page'])
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

        $processTable = with(new Process)->getTable();
        $categoryTable = with(new ProcessCategory)->getTable();
        $userTable = with(new User)->getTable();

        if (empty($request->input('order_by'))) {
            // if no sort option is sent
            $query = Process::with(['category', 'user'])
                ->select("$processTable.*")
                ->leftJoin($categoryTable, function ($join) use ($processTable, $categoryTable) {
                    $join->on("$categoryTable.id", "=", "$processTable.process_category_id");
                })->orderBy("$categoryTable.name")
                ->orderBy("$processTable.name");
        }
        else {
            $query = Process::with(['category', 'user'])
                ->orderBy($options['sort_by'], $options['sort_order']);
        }

        $query->where(function ($query) use ($processTable) {
            $query->where("$processTable.status", '=', 'ACTIVE');
        });

        if (!empty($options['filter'])) {
            // We want to search off of name and description and category name
            // Cannot join on table because of Eloquent's lack of specific table column names in generated SQL
            // See: https://github.com/laravel/ideas/issues/347
            $filter = '%' . $options['filter'] . '%';
            $query->where(function ($query) use ($filter, $userTable, $processTable, $categoryTable) {
                $query->Where("$processTable.name", 'like', $filter)
                    ->orWhere("$processTable.description", 'like', $filter)
                    ->orWhere("$processTable.status", 'like', $filter)
                    ->orWhere(function ($q) use ($filter, $categoryTable) {
                        $q->whereHas('category', function ($query) use ($filter, $categoryTable) {
                            $query->where("$categoryTable.name", 'like', $filter);
                        });
                    })
                    ->orWhere(function ($q) use ($filter, $userTable) {
                        $q->whereHas('user', function ($query) use ($filter, $userTable) {
                            $query->where("$userTable.firstname", 'like', $filter)
                                ->where("$userTable.lastname", 'like', $filter);
                        });
                    });
            });
        }

        $processes = $query->paginate($options['per_page'])
            ->appends($options);

        return fractal($processes, new ProcessTransformer())->respond();
    }

    /**
     * Adds where conditions to the passed query, depending of the delay value
     * @param $query
     * @param $delay
     */
    private function addDelayConditionsToQuery($query, $delay)
    {
        if ($delay === 'overdue') {
            $query->where('task_due_date', '<=', Carbon::now()->toDateString());
        }

        if ($delay === 'at_risk') {
            $query->where('risk_date', '<', Carbon::now()->toDateString())
                ->where('task_due_date', '>', Carbon::now()->toDateString());
        }

        if ($delay === 'on_time') {
            $query->where(function($q) {
                $q->where('risk_date', '>=', Carbon::now()->toDateString())
                    ->orWhereNull('task_due_date');

            });
        }
    }
}
