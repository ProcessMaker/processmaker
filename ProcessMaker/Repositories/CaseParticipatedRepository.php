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
            CaseParticipated::create([
                'user_id' => $token->user->id,
                'case_number' => $case->case_number,
                'case_title' => $case->case_title,
                'case_title_formatted' => $case->case_title_formatted,
                'case_status' => $case->case_status,
                'processes' => $case->processes,
                'requests' => $case->requests,
                'request_tokens' => [$token->getKey()],
                'tasks' => [
                    [
                        'id' => $token->getKey(),
                        'element_id' => $token->element_id,
                        'name' => $token->element_name,
                        'process_id' => $token->process_id,
                    ],
                ],
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

            // Add the token data to the request_tokens
            $requestTokens = $caseParticipated->request_tokens->push($token->getKey())
                ->unique()
                ->values();
            // Add the task data to the tasks
            $tasks = $caseParticipated->tasks->push([
                'id' => $token->getKey(),
                'element_id' => $token->element_id,
                'name' => $token->element_name,
                'process_id' => $token->process_id,
            ])
            ->unique('id')
            ->values();

            $caseParticipated->update([
                'case_title' => $case->case_title,
                'case_title_formatted' => $case->case_title_formatted,
                'case_status' => $case->case_status,
                'processes' => $case->processes,
                'requests' => $case->requests,
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
