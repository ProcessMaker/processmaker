<?php

namespace ProcessMaker\Http\Controllers\Api\Cases;

use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Delegation;
use ProcessMaker\Model\Application;
use ProcessMaker\Transformers\RequestsTransformer;

/**
 * API endpoint for returning Cases
 */
class RequestsController extends Controller
{
    /**
     * Returns the list of cases that the current user has created
     *
     * @param Request $request
     * @return array $result result of the query
     */

    public function index(Request $request)
    {
        $currentUserId = \Auth::user()->id;

        $cases = Delegation::select(
            'APPLICATION.id as instance_id',
            'APPLICATION.uid as instance_uid',
            'APPLICATION.APP_STATUS AS instance_status',
            'processes.uid AS process_uid',
            'APPLICATION.APP_CREATE_DATE AS instance_create_date',
            'APPLICATION.APP_FINISH_DATE AS instance_finish_date',
            'APPLICATION.APP_UPDATE_DATE AS instance_update_date',
            'APPLICATION.APP_TITLE AS instance_title',
            'users.uid AS user_uid',
            'tasks.uid AS task_uid',
            'delegations.init_date',
            'delegations.finish_date',
            'delegations.task_due_date',
            'delegations.risk_date',
            \DB::raw("concat(task_due_date, '|' ,case when now() < risk_date THEN 'on_time' when now() <= task_due_date and now() >= risk_date then 'at_risk' else 'overdue' END) as due_date_delay"),
            'tasks.title AS task_title',
            'tasks.type AS task_type',
            'users.lastname',
            'users.firstname',
            'users.username',
            'processes.name AS process_name'
        )
        ->leftJoin('APPLICATION', 'delegations.application_id', '=', 'APPLICATION.id')
        ->leftJoin('tasks', 'delegations.task_id', '=', 'tasks.id')
        ->leftJoin('users', 'delegations.user_id', '=', 'users.id')
        ->leftJoin('processes', 'APPLICATION.process_id', '=', 'processes.id');

        if ($request->has('delay') && $request->delay <> '') {
            $now = Carbon::now();
            if ($request->delay === 'overdue') {
                $cases->where('delegations.task_due_date', '<', $now->format('Y-m-d 23:59:59'));
            }

            if ($request->delay === 'on_time') {
                $cases->where('delegations.risk_date', '>', $now->format('Y-m-d 23:59:59'));
            }

            if ($request->delay === 'at_risk') {
                $cases->where('delegations.task_due_date', '>=', $now->format('Y-m-d 23:59:59'));
                $cases->where('delegations.risk_date', '<=', $now->format('Y-m-d 23:59:59'));
            }
        }

        $options = [
            'filter' => $request->input('filter', ''),
            'current_page' => $request->input('current_page', 1),
            'per_page' => $request->input('per_page', 10),
            'sort_by' => $request->input('sort_by', 'username'),
            'order_direction' => $request->input('order_direction', 'ASC'),
        ];

        $response = $cases->paginate($options['per_page'])
                        ->appends($options);

        return fractal($response, new RequestsTransformer())->respond();
    }
}
