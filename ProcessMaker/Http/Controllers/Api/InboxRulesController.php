<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Models\InboxRule;
use ProcessMaker\Models\InboxRuleLog;

class InboxRulesController extends Controller
{
    /**
     * Retrieve all inbox rules.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $order_by = $request->input('order_by', 'id');
        $order_direction = $request->input('order_direction', 'desc');
        $per_page = $request->input('per_page', 10);
        $filter = $request->input('filter', '');

        $query = InboxRule::query();

        if (!empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query->where('name', 'like', '%' . $filter . '%');
            });
        }

        $response = $query->orderBy($order_by, $order_direction)
            ->paginate($per_page);

        return new ApiCollection($response);
    }

    /**
     * Retrieve a specific inbox rule by its ID.
     *
     * @param int $idInboxRule
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $idInboxRule)
    {
        return new ApiResource(
            InboxRule::findOrFail($idInboxRule)
        );
    }

    /**
     * Store a new inbox rule.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // We always create a new saved search when we create a new inbox rule
        $savedSearch = InboxRule::createSavedSearch([
            'columns' => $request->columns,
            'advanced_filter' => $request->advanced_filter,
            'pmql' => $request->pmql,
            'user_id' => $request->user()->id,
        ]);

        $request->applyToCurrentInboxMatchingTasks;
        $request->applyToFutureTasks;
        $data = [
            'name' => $request->ruleName,
            'user_id' => $request->user()->id,
            'active' => true,
            'end_date' => $request->deactivationDate,
            'saved_search_id' => $savedSearch->id,
            'process_request_token_id' => $request->taskId,
            'mark_as_priority' => $request->actionsTask === 'priority' ? true : false,
            'reassign_to_user_id' => $request->selectedPerson,
            'make_draft' => true,
            'submit_data' => true,
            'data' => null,
        ];
        InboxRule::create($data);

        return response([], 204);
    }

    /**
     * Update an existing inbox rule.
     *
     * @param Request $request
     * @param int $idInboxRule
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $idInboxRule)
    {
        $request->applyToCurrentInboxMatchingTasks;
        $request->applyToFutureTasks;
        $data = [
            'name' => $request->ruleName,
            'user_id' => 2,
            'active' => true,
            'end_date' => $request->deactivationDate,
            'saved_search_id' => 1,
            'process_request_token_id' => 1,
            'mark_as_priority' => $request->actionsTask === 'priority' ? true : false,
            'reassign_to_user_id' => $request->selectedPerson,
            'make_draft' => true,
            'submit_data' => true,
            'data' => null,
        ];
        InboxRule::findOrFail($idInboxRule)->update($data);

        return response([], 204);
    }

    /**
     * Delete an existing inbox rule.
     *
     * @param int $idInboxRule
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($idInboxRule)
    {
        InboxRule::findOrFail($idInboxRule)->delete();

        return response([], 204);
    }

    /**
     * Retrieve inbox rule log.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function executionLog(Request $request)
    {
        $response = InboxRuleLog::where('user_id', $request->user()->id)
            ->with('task')
            ->with('task.processRequest')
            ->orderBy('id', 'DESC')
            ->paginate($request->input('per_page', 10));

        return new ApiCollection($response);
    }
}
