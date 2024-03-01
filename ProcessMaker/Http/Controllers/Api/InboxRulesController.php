<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
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
        $order = $request->input('order', 'DESC');
        $per_page = $request->input('per_page', 10);
        $response = InboxRule::orderBy('id', $order)
            ->get();
        return new ApiCollection($response);
    }

    /**
     * Retrieve a specific inbox rule by its ID.
     *
     * @param int $inbox_rule_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($inbox_rule_id)
    {
        // Logic to retrieve a specific inbox rule
    }

    /**
     * Store a new inbox rule.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Logic to store a new inbox rule
    }

    /**
     * Update an existing inbox rule.
     *
     * @param Request $request
     * @param int $inbox_rule_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $inbox_rule_id)
    {
        // Logic to update an existing inbox rule
    }

    /**
     * Delete an existing inbox rule.
     *
     * @param int $inbox_rule_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($inbox_rule_id)
    {
        // Logic to delete an existing inbox rule
    }

    /**
     * Retrieve inbox rule log.
     *
     * @param int $inbox_rule_id
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
