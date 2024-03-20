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
            'columns' => $request->input('columns'),
            'advanced_filter' => $request->input('advanced_filter'),
            'pmql' => $request->input('pmql') ?? '',
            'user_id' => $request->user()->id,
        ]);

        $inboxRule = InboxRule::create([
            'name' => $request->input('name'),
            'user_id' => $request->user()->id,
            'active' => $request->input('active', false),
            'end_date' => $request->input('end_date'),
            'saved_search_id' => $savedSearch->id,
            'process_request_token_id' => $request->input('process_request_token_id'),
            'mark_as_priority' => $request->input('mark_as_priority', false),
            'reassign_to_user_id' => $request->input('reassign_to_user_id'),
            'make_draft' => $request->input('make_draft', false),
            'submit_data' => $request->input('submit_data', false),
            'submit_button' => $request->input('submit_button'),
            'data' => $request->input('data'),
        ]);

        if ($request->get('apply_to_current_tasks', false)) {
            $inboxRule->applyToExistingTasks();
        }

        return response([], 204);
    }

    /**
     * Update an existing inbox rule.
     *
     * @param Request $request
     * @param int $idInboxRule
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, InboxRule $inboxRule)
    {
        $inboxRule->update([
            'name' => $request->input('name'),
            'active' => $request->input('active', false),
            'end_date' => $request->input('end_date'),
            'mark_as_priority' => $request->input('mark_as_priority', false),
            'reassign_to_user_id' => $request->input('reassign_to_user_id'),
            'make_draft' => $request->input('make_draft', false),
            'submit_data' => $request->input('submit_data', false),
            'submit_button' => $request->input('submit_button'),
            'data' => $request->input('data'),
        ]);

        $inboxRule->savedSearch->update([
            'columns' => $request->input('columns'),
            'advanced_filter' => $request->input('advanced_filter'),
        ]);

        if ($request->get('apply_to_current_tasks', false)) {
            $inboxRule->applyToExistingTasks();
        }

        return response([], 204);
    }

    /**
     * Delete an existing inbox rule.
     *
     * @param int $idInboxRule
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(InboxRule $inboxRule)
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

    /**
     * Update an existing inbox rule.
     *
     * @param Request $request
     * @param int $idInboxRule
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateActive(Request $request, $idInboxRule)
    {
        $data = [
            'active' => $request->active,
        ];
        InboxRule::findOrFail($idInboxRule)->update($data);

        return response([], 204);
    }
}
