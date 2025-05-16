<?php

namespace ProcessMaker\Http\Controllers\Api;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;

class CaseController extends Controller
{
    public function getStageCase($case_number)
    {
        $allRequests = ProcessRequest::where('case_number', $case_number)->get();
        // Check if any requests were found
        if ($allRequests->isEmpty()) {
            return response()->json(['error' => 'No requests found for this case number'], 404);
        }
        $parentRequest = null;
        $requestCount = $allRequests->count();
        // Search the parent request parent_request_id and load $request
        foreach ($allRequests as $request) {
            if (is_null($request->parent_request_id)) {
                $parentRequest = $request;
                break;
            }
        }

        $stagesPerCase = $this->getStagesSummary($parentRequest->id, $parentRequest->process_id);

        $responseData = [
            'parentRequest' => [
                'id' => $parentRequest->id,
                'case_number' => $parentRequest->case_number,
                'status' => $parentRequest->status,
                'completed_at' => $parentRequest->completed_at,
            ],
            'requestCount' => $requestCount,
            'all_stages' => [],
            'current_stage' => [],
            'stages_per_case' => $stagesPerCase,
        ];

        return response()->json($responseData);
    }

    /**
     * Get the stages summary based on the provided request ID.
     *
     * This method retrieves the stages associated with a specific process request ID.
     * It initializes a list of all possible stages and queries the ProcessRequestToken
     * model to get the current stages for the given request ID.
     * The method returns an array of stage results, including their status and completion date.
     *
     * @param int $requestId The ID of the process request to get stages for.
     * @return array An array of stage results, each containing the stage ID, name, status,
     *               and completion date.
     */
    public function getStagesSummary($requestId, $processId)
    {
        $process = Process::where('id', $processId)->first();
        if ($process && !empty($process->stages)) {
            $allStages = json_decode($process->stages, true);
        } else {
            // TO_DO: remove this harcode values
            $allStages = [
                ['id' => 1, 'name' => 'Stage A'],
                ['id' => 2, 'name' => 'Stage B'],
                ['id' => 3, 'name' => 'Stage C'],
            ];
        }

        $allCurrentStages = ProcessRequestToken::where('process_request_id', $requestId)
            ->select('stage_id', 'stage_name', 'status', 'completed_at')
            ->get()
            ->toArray();
        if (empty($allCurrentStages)) {
            // TO_DO: remove this harcode values
            $allCurrentStages = [
                ['stage_id' => 1, 'stage_name' => 'Stage A', 'status' => 'CLOSED', 'completed_at' => '2025-04-23 21:22:34'],
                ['stage_id' => 2, 'stage_name' => 'Stage B', 'status' => 'ACTIVE', 'completed_at' => 'null'],
            ];
        }

        // Helper to map status
        $mapStatus = function ($status) {
            if ($status === 'CLOSED') {
                return 'Done';
            } elseif ($status === 'ACTIVE') {
                return 'In Progress';
            } else {
                return 'Pending';
            }
        };

        $stageResult = [];
        // Initialize stage counts with zero for all stages
        foreach ($allStages as $stage) {
            $stageData = [
                'id' => $stage['id'],
                'name' => $stage['name'],
                'status' => 'Pending',
                'completed_at' => '',
            ];

            foreach ($allCurrentStages as $task) {
                if ($task['stage_id'] === $stage['id']) {
                    $stageData['status'] = $mapStatus($task['status']);
                    $stageData['completed_at'] = $task['completed_at'] ?? '';
                    break;
                }
            }

            $stageResult[] = $stageData;
        }

        return $stageResult;
    }
}
