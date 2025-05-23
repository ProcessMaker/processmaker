<?php

namespace ProcessMaker\Http\Controllers\Api;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;

class CaseController extends Controller
{
    /**
     * Get stage information for cases
     */
    public function getStagePerCase($case_number = null)
    {
        if (!empty($case_number)) {
            $responseData = $this->getSpecificCaseStages($case_number);

            return response()->json($responseData);
        }

        $responseData = [
            'parentRequest' => [],
            'requestCount' => 0,
            'all_stages' => [],
            'current_stage' => [],
            'stages_per_case' => $this->getDefaultCaseStages(),
        ];

        return response()->json($responseData);
    }

    /**
     * Get specific case stages information
     * @param string $caseNumber The unique identifier of the case to retrieve stages for
     * @return array
     */
    private function getSpecificCaseStages($caseNumber)
    {
        $allRequests = ProcessRequest::where('case_number', $caseNumber)->get();
        // Check if any requests were found
        if ($allRequests->isEmpty()) {
            return $this->getDefaultCaseStages();
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

        $stagesPerCase = $this->getStagesSummary($parentRequest);

        return [
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
    }

    /**
     * Get default case stages with status handling
     *
     * @param string|null $status The status to set for the stages
     * @return array
     */
    private function getDefaultCaseStages($status = null)
    {
        return [
            [
                'id' => 0,
                'name' => 'In Progress',
                'status' => $this->mapStatus($status, 'In Progress'),
                'completed_at' => '',
            ],
            [
                'id' => 0,
                'name' => 'Completed',
                'status' => $this->mapStatus($status, 'Completed'),
                'completed_at' => '',
            ],
        ];
    }

    /**
     * Map the status for each stage based on the input status
     *
     * @param string|null $status The input status to map
     * @param string $stageName The name of the stage ('In Progress' or 'Completed')
     * @return string The mapped status
     */
    private function mapStatus($status, $stageName)
    {
        if ($status === 'COMPLETED') {
            return 'Done';
        }

        if ($status === 'ACTIVE') {
            return match ($stageName) {
                'In Progress' => 'In Progress',
                'Completed' => 'Pending',
                default => 'Pending'
            };
        }

        return 'Pending';
    }

    /**
     * Get the stages summary based on the provided request.
     *
     * @param $requestId
     * @return array An array of stage results, each containing the stage ID, name, status,
     *               and completion date.
     */
    private function getStagesSummary(ProcessRequest $request)
    {
        $requestId = $request->id;
        $processId = $request->process_id;
        $process = Process::where('id', $processId)->first();
        if ($process && !empty($process->stages)) {
            $allStages = json_decode($process->stages, true);
        } else {
            // Return the default stages if the process does not have
            return $this->getDefaultCaseStages($request->status);
        }

        $allCurrentStages = ProcessRequestToken::where('process_request_id', $requestId)
            ->select('stage_id', 'stage_name', 'status', 'completed_at')
            ->get()
            ->toArray();
        if (empty($allCurrentStages)) {
            // TO_DO: define what happen if the process does not have task, is a valid use case
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
