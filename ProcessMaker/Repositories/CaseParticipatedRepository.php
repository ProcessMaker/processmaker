<?php

namespace ProcessMaker\Repositories;

use Illuminate\Support\Facades\Log;
use ProcessMaker\Models\CaseParticipated;
use ProcessMaker\Models\CaseStarted;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;

class CaseParticipatedRepository
{
    /**
     * This property is used to store an instance of `CaseParticipated`
     * when a case participated is updated.
     * @var CaseParticipated|null
     */
    protected ?CaseParticipated $caseParticipated;

    /**
     * Store a new case participated.
     *
     * @param CaseStarted $case
     * @param TokenInterface $token
     * @return void
     */
    public function create(CaseStarted $case, TokenInterface $token): void
    {
        if ($this->checkIfCaseParticipatedExist($token->user->id, $case->case_number)) {
            return;
        }

        try {
            CaseParticipated::create([
                'user_id' => $token->user->id,
                'case_number' => $case->case_number,
                'case_title' => $case->case_title,
                'case_title_formatted' => $case->case_title_formatted,
                'case_status' => $case->case_status,
                'processes' => CaseUtils::storeProcesses($token->processRequest, collect()),
                'requests' => CaseUtils::storeRequests($token->processRequest, collect()),
                'request_tokens' => CaseUtils::storeRequestTokens($token->getKey(), collect()),
                'tasks' => CaseUtils::storeTasks($token, collect()),
                'participants' => $case->participants,
                'initiated_at' => $case->initiated_at,
                'completed_at' => null,
            ]);
        } catch (\Exception $e) {
            Log::error('CaseException: ' . $e->getMessage());
            Log::error('CaseException: ' . $e->getTraceAsString());
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
            if (!$this->checkIfCaseParticipatedExist($token->user->id, $case->case_number)) {
                return;
            }

            $this->caseParticipated->updateOrFail([
                'case_title' => $case->case_title,
                'case_title_formatted' => $case->case_title_formatted,
                'case_status' => $case->case_status,
                'processes' => CaseUtils::storeProcesses($token->processRequest, $this->caseParticipated->processes),
                'requests' => CaseUtils::storeRequests($token->processRequest, $this->caseParticipated->requests),
                'request_tokens' => CaseUtils::storeRequestTokens($token->getKey(), $this->caseParticipated->request_tokens),
                'tasks' => CaseUtils::storeTasks($token, $this->caseParticipated->tasks),
                'participants' => $case->participants,
            ]);
        } catch (\Exception $e) {
            Log::error('CaseException: ' . $e->getMessage());
            Log::error('CaseException: ' . $e->getTraceAsString());
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
            Log::error('CaseException: ' . $e->getMessage());
            Log::error('CaseException: ' . $e->getTraceAsString());
        }
    }

    /**
     * Check if a case participated exists.
     * If it exists, store the instance in the property.
     * The property is used to update the JSON fields of the case participated.
     *
     * @param int $userId
     * @param int $caseNumber
     *
     * @return bool
     */
    private function checkIfCaseParticipatedExist(int $userId, int $caseNumber): bool
    {
        $this->caseParticipated = CaseParticipated::where('user_id', $userId)
            ->where('case_number', $caseNumber)
            ->first();

        return !is_null($this->caseParticipated);
    }
}
