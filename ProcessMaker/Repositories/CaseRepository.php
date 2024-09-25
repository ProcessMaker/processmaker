<?php

namespace ProcessMaker\Repositories;

use Carbon\Carbon;
use ProcessMaker\Contracts\CaseRepositoryInterface;
use ProcessMaker\Models\CaseStarted;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;

class CaseRepository implements CaseRepositoryInterface
{
    /**
     * @var CaseStarted|null
     */
    protected ?CaseStarted $case;

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
        if (is_null($instance->case_number) || $this->checkIfCaseStartedExist($instance->case_number)) {
            $this->updateSubProcesses($instance);

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
                    'parent_request_id' => $instance->parentRequest->id,
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
            if (!$this->checkIfCaseStartedExist($instance->case_number)) {
                return;
            }

            $this->case->case_title = $instance->case_title;
            $this->case->case_status = $instance->status === 'ACTIVE' ? 'IN_PROGRESS' : $instance->status;

            $this->case->request_tokens = $this->case->request_tokens->push($token->getKey())
                ->unique()
                ->values();

            if (in_array($token->element_type, ['task'])) {
                $this->case->tasks = $this->case->tasks->push([
                    'id' => $token->getKey(),
                    'element_id' => $token->element_id,
                    'name' => $token->element_name,
                    'process_id' => $token->process_id,
                ])
                ->unique('id')
                ->values();
            }

            $this->updateParticipants($token);

            $this->case->saveOrFail();
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
        if (!is_null($instance->parent_request_id)) {
            return;
        }

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
     * @param TokenInterface $token
     * @return void
     */
    private function updateParticipants(TokenInterface $token): void
    {
        $user = $token->user;

        if (!$user) {
            return;
        }

        $participantExists = $this->case->participants->contains(function ($participant) use ($user) {
            return $participant['id'] === $user->id;
        });

        if (!$participantExists) {
            $this->case->participants->push([
                'id' => $user->id,
                'name' => $user->getFullName(),
                'title' => $user->title,
                'avatar' => $user->avatar,
            ]);

            $this->caseParticipatedRepository->create($this->case, $token);
        }

        $this->caseParticipatedRepository->update($this->case, $token);
    }

    /**
     * Check if the case started exist.
     *
     * @param int $caseNumber
     * @return bool
     */
    private function checkIfCaseStartedExist(int $caseNumber): bool
    {
        $this->case = CaseStarted::where('case_number', $caseNumber)->first();

        return !is_null($this->case);
    }

    /**
     * Update the processes and requests of the case started.
     *
     * @param ExecutionInstanceInterface $instance
     * @return void
     */
    private function updateSubProcesses(ExecutionInstanceInterface $instance): void
    {
        if (is_null($instance->parent_request_id)) {
            return;
        }

        try {
            // Store the sub-processes and requests
            $this->case->processes = $this->case->processes->push([
                'id' => $instance->process->id,
                'name' => $instance->process->name,
            ])
            ->unique('id')
            ->values();

            $this->case->requests = $this->case->requests->push([
                'id' => $instance->id,
                'name' => $instance->name,
                'parent_request_id' => $instance->parentRequest->id,
            ])
            ->unique('id')
            ->values();

            $this->case->saveOrFail();
        } catch (\Exception $th) {
            \Log::error($th->getMessage());
        }
    }
}
