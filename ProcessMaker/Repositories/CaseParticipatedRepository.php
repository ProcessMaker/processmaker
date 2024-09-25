<?php

namespace ProcessMaker\Repositories;

use ProcessMaker\Models\CaseParticipated;
use ProcessMaker\Models\CaseStarted;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;

class CaseParticipatedRepository
{
    /**
     * Store a new case participated.
     *
     * @param CaseStarted $case
     * @param TokenInterface $token
     * @return void
     */
    public function create(CaseStarted $case, TokenInterface $token): void
    {
        try {
            $processes = [
                [
                    'id' => $token->processRequest->process->id,
                    'name' => $token->processRequest->process->name,
                ],
            ];

            $requests = [
                [
                    'id' => $token->processRequest->id,
                    'name' => $token->processRequest->name,
                    'parent_request_id' => $token->processRequest->parentRequest->id,
                ],
            ];

            $tasks = collect();

            if (in_array($token->element_type, ['task'])) {
                $tasks->push([
                    'id' => $token->getKey(),
                    'element_id' => $token->element_id,
                    'name' => $token->element_name,
                    'process_id' => $token->process_id,
                ]);
            }

            CaseParticipated::create([
                'user_id' => $token->user->id,
                'case_number' => $case->case_number,
                'case_title' => $case->case_title,
                'case_title_formatted' => $case->case_title_formatted,
                'case_status' => $case->case_status,
                'processes' => $processes,
                'requests' => $requests,
                'request_tokens' => [$token->getKey()],
                'tasks' => $tasks,
                'participants' => $case->participants,
                'initiated_at' => $case->initiated_at,
                'completed_at' => null,
            ]);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }
    }

    /**
     * Update the case participated.
     *
     * @param CaseStarted $case
     * @param TokenInterface $token
     * @return void
     */
    public function update(CaseStarted $case, TokenInterface $token)
    {
        try {
            $caseParticipated = CaseParticipated::where('user_id', $token->user->id)
                ->where('case_number', $case->case_number)
                ->first();

            if (is_null($caseParticipated)) {
                return;
            }

            // Store the sub-processes and requests
            $processes = $caseParticipated->processes->push([
                'id' => $token->processRequest->process->id,
                'name' => $token->processRequest->process->name,
            ])
            ->unique('id')
            ->values();

            $requests = $caseParticipated->requests->push([
                'id' => $token->processRequest->id,
                'name' => $token->processRequest->name,
                'parent_request_id' => $token->processRequest->parentRequest->id,
            ])
            ->unique('id')
            ->values();

            // Add the token data to the request_tokens
            $requestTokens = $caseParticipated->request_tokens->push($token->getKey())
                ->unique()
                ->values();
            // Add the task data to the tasks
            $tasks = $caseParticipated->tasks;

            if (in_array($token->element_type, ['task'])) {
                $tasks = $tasks->push([
                    'id' => $token->getKey(),
                    'element_id' => $token->element_id,
                    'name' => $token->element_name,
                    'process_id' => $token->process_id,
                ])
                ->unique('id')
                ->values();
            }

            $caseParticipated->update([
                'case_title' => $case->case_title,
                'case_title_formatted' => $case->case_title_formatted,
                'case_status' => $case->case_status,
                'processes' => $processes,
                'requests' => $requests,
                'request_tokens' => $requestTokens,
                'tasks' => $tasks,
                'participants' => $case->participants,
            ]);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }
    }

    /**
     * Update the status of a case participated.
     *
     * @param int $caseNumber
     * @param array $statusData
     * @return void
     */
    public function updateStatus(int $caseNumber, array $statusData)
    {
        try {
            CaseParticipated::where('case_number', $caseNumber)
                ->update($statusData);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }
    }
}
