<?php

namespace ProcessMaker\Repositories;

use Carbon\Carbon;
use ProcessMaker\Contracts\CaseRepositoryInterface;
use ProcessMaker\Models\CaseStarted;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;

class CaseRepository implements CaseRepositoryInterface
{
    public function __construct(protected CaseParticipatedRepository $caseParticipatedRepository)
    {
    }
    /**
     * Store a new case started.
     *
     * @param ExecutionInstanceInterface $instance
     * @return void
     */
    public function create(ExecutionInstanceInterface $instance): void
    {
        if ($this->checkIfCaseStartedExist($instance->case_number)) {
            return;
        }

        try {
            $processes = [
                [
                    'id' => $instance->process->id,
                    'name' => $instance->process->name,
                ],
            ];

            $requests = [
                [
                    'id' => $instance->id,
                    'name' => $instance->name,
                    'parent_request_id' => $instance->parentRequest->id ?? 0,
                ],
            ];

            CaseStarted::create([
                'case_number' => $instance->case_number,
                'user_id' => $instance->user_id,
                'case_title' => $instance->case_title,
                'case_title_formatted' => $instance->case_title_formatted,
                'case_status' => 'IN_PROGRESS',
                'processes' => $processes,
                'requests' => $requests,
                'request_tokens' => [],
                'tasks' => [],
                'participants' => [],
                'initiated_at' => $instance->initiated_at,
                'completed_at' => null,
            ]);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }
    }

    /**
     * Update the case started.
     *
     * @param ExecutionInstanceInterface $instance
     * @param TokenInterface $token
     * @return void
     */
    public function update(ExecutionInstanceInterface $instance, TokenInterface $token): void
    {
        try {
            $case = CaseStarted::where('case_number', $instance->case_number)->first();

            if (is_null($case)) {
                return;
            }

            $case->case_title = $instance->case_title;
            $case->case_status = $instance->status === 'ACTIVE' ? 'IN_PROGRESS' : $instance->status;

            $case->request_tokens = $case->request_tokens->push($token->getKey())
                ->unique()
                ->values();

            if (!in_array($token->element_type, ['scriptTask'])) {
                $case->tasks = $case->tasks->push([
                    'id' => $token->getKey(),
                    'element_id' => $token->element_id,
                    'name' => $token->element_name,
                    'process_id' => $token->process_id,
                ])
                ->unique('id')
                ->values();
            }

            $this->updateParticipants($case, $token);

            $case->saveOrFail();
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }
    }

    /**
     * Update the status of a case started.
     *
     * @param ExecutionInstanceInterface $instance
     * @return void
     */
    public function updateStatus(ExecutionInstanceInterface $instance): void
    {
        try {
            $data = [
                'case_status' => $instance->status,
            ];

            if ($instance->status === 'COMPLETED') {
                $data['completed_at'] = Carbon::now();
            }

            // Update the case started and case participated
            CaseStarted::where('case_number', $instance->case_number)->update($data);
            $this->caseParticipatedRepository->updateStatus($instance->case_number, $data);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }
    }

    /**
     * Update the participants of the case started.
     *
     * @param CaseStarted $case
     * @param TokenInterface $token
     * @return void
     */
    private function updateParticipants(CaseStarted $case, TokenInterface $token): void
    {
        $user = $token->user;

        if (!$user) {
            return;
        }

        $participantExists = $case->participants->contains(function ($participant) use ($user) {
            return $participant['id'] === $user->id;
        });

        if (!$participantExists) {
            $case->participants->push([
                'id' => $user->id,
                'name' => $user->getFullName(),
                'title' => $user->title,
                'avatar' => $user->avatar,
            ]);

            $this->caseParticipatedRepository->create($case, $token);
        }

        $this->caseParticipatedRepository->update($case, $token);
    }

    /**
     * Check if the case started exist.
     *
     * @param int $caseNumber
     * @return bool
     */
    private function checkIfCaseStartedExist(int $caseNumber): bool
    {
        return CaseStarted::where('case_number', $caseNumber)->count() > 0;
    }
}
