<?php

namespace ProcessMaker\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Models\CaseParticipated;
use ProcessMaker\Models\CaseStarted;

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
     * @param int $userId
     * @return void
     */
    public function create(CaseStarted $case, int $userId): void
    {
        try {
            CaseParticipated::updateOrCreate(
                [
                    'case_number' => $case->case_number,
                    'user_id' => $userId,
                ],
                $this->mapCaseToArray($case, $userId)
            );
        } catch (\Exception $e) {
            $this->logException($e);
        }
    }

    /**
     * Update the cases participated.
     *
     * @param CaseStarted $case
     * @return void
     */
    public function update(CaseStarted $case): void
    {
        try {
            CaseParticipated::where('case_number', $case->case_number)
                ->update($this->mapCaseToArray($case));
        } catch (\Exception $e) {
            $this->logException($e);
        }
    }

    /**
     * Maps properties of a `CaseStarted` object to an array, optionally including a user ID.
     *
     * @param CaseStarted case Takes a `CaseStarted` object and parameter as input and returns an array with specific
     * properties mapped from the `CaseStarted` object.
     * @param int userId Takes an optional `userId` parameter.
     *
     * @return array An array containing various properties of a CaseStarted object. If a userId is
     * provided, it will also include the user_id in the returned array.
     */
    private function mapCaseToArray(CaseStarted $case, int $userId = null): array
    {
        $data = [
            'case_number' => $case->case_number,
            'case_title' => $case->case_title,
            'case_title_formatted' => $case->case_title_formatted,
            'case_status' => $case->case_status,
            'processes' => $case->processes,
            'requests' => $case->requests,
            'request_tokens' => $case->request_tokens,
            'tasks' => $case->tasks,
            'participants' => $case->participants,
            'initiated_at' => $case->initiated_at,
            'completed_at' => $case->completed_at,
            'keywords' => $case->keywords,
        ];

        if ($userId !== null) {
            $data['user_id'] = $userId;
        }

        return $data;
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
            $this->logException($e);
        }
    }

    private function logException(\Exception $e): void
    {
        Log::error('CaseException: ' . $e->getMessage());
        Log::error('CaseException: ' . $e->getTraceAsString());
    }
}
