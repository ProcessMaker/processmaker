<?php

namespace ProcessMaker\Repositories;

use Carbon\Carbon;
use ProcessMaker\Contracts\CaseRepositoryInterface;
use ProcessMaker\Models\CaseParticipated;
use ProcessMaker\Models\CaseStarted;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;

class CaseRepository implements CaseRepositoryInterface
{
    public function create(ExecutionInstanceInterface $instance): void
    {
        if ($this->checkIfCaseStartedExist($instance->case_number)) {
            return;
        }

        try {
            $process = [
                'id' => $instance->process->id,
                'name' => $instance->process->name,
            ];

            $request = [
                'id' => $instance->id,
                'name' => $instance->name,
                'parent_request_id' => $instance->parentRequest->id ?? 0,
            ];

            CaseStarted::create([
                'case_number' => $instance->case_number,
                'user_id' => $instance->user_id,
                'case_title' => $instance->case_title,
                'case_title_formatted' => $instance->case_title_formatted,
                'case_status' => 'IN_PROGRESS',
                'processes' => [$process],
                'requests' => [$request],
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

    public function update(ExecutionInstanceInterface $instance, TokenInterface $token): void
    {
        try {
            $case = CaseStarted::where('case_number', $instance->case_number)->first();
            $user = $token->user;

            $this->updateRequestTokens($case, $token);
            $this->updateTasks($case, $token);
            $this->updateParticipants($case, $user);

            $case->saveOrFail();
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            dd($e->getMessage());
        }
    }

    public function updateStatus(ExecutionInstanceInterface $instance): void
    {
        try {
            $data = [
                'case_status' => $instance->status,
            ];

            if ($instance->status === 'COMPLETED') {
                $data['completed_at'] = Carbon::now();
            }

            CaseStarted::where('case_number', $instance->case_number)->update($data);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            dd($e->getMessage());
        }
    }

    private function updateRequestTokens(CaseStarted $case, TokenInterface $token)
    {
        $requestTokenExists = $case->request_tokens->contains($token->getKey());

        if (!$requestTokenExists) {
            $case->request_tokens->push($token->getKey());
        }
    }

    private function updateTasks(CaseStarted $case, TokenInterface $token)
    {
        $taskExists = $case->tasks->contains(function ($task) use ($token) {
            return $task['id'] === $token->element_id;
        });

        if (!$taskExists) {
            $case->tasks->push([
                'id' => $token->element_id,
                'name' => $token->element_name,
            ]);
        }
    }

    private function updateParticipants(CaseStarted $case, User | null $user)
    {
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
            ]);

            CaseParticipated::create([
                'user_id' => $user->id,
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
            ]);
        }
    }

    private function checkIfCaseStartedExist(int $caseNumber): bool
    {
        return CaseStarted::where('case_number', $caseNumber)->count() > 0;
    }
}
