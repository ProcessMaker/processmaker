<?php

namespace ProcessMaker\Repositories;

use Carbon\Carbon;
use ProcessMaker\Contracts\CaseRepositoryInterface;
use ProcessMaker\Models\CaseParticipated;
use ProcessMaker\Models\CaseStarted;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;

class CaseRepository implements CaseRepositoryInterface
{
    const CASE_STATUS_ACTIVE = 'ACTIVE';

    /**
     * This property is used to store an instance of `CaseStarted`
     * when a case started is updated.
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
        if (is_null($instance->case_number)) {
            \Log::error('Case number is required. instance: ' . $instance->getKey());

            return;
        }

        if ($this->checkIfCaseStartedExist($instance->case_number)) {
            $this->updateSubProcesses($instance);

            return;
        }

        try {
            CaseStarted::create([
                'case_number' => $instance->case_number,
                'user_id' => $instance->user_id,
                'case_title' => $instance->case_title,
                'case_title_formatted' => $instance->case_title_formatted,
                'case_status' => $instance->status === self::CASE_STATUS_ACTIVE ? 'IN_PROGRESS' : $instance->status,
                'processes' => CaseUtils::storeProcesses($instance, collect()),
                'requests' => CaseUtils::storeRequests($instance, collect()),
                'request_tokens' => [],
                'tasks' => [],
                'participants' => [],
                'initiated_at' => $instance->initiated_at,
                'completed_at' => null,
            ]);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            \Log::error($e->getTraceAsString());
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
        if (is_null($instance->case_number)) {
            \Log::error('Case number is required. instance: ' . $instance->getKey());

            return;
        }

        try {
            if (!$this->checkIfCaseStartedExist($instance->case_number)) {
                \Log::error('Case number not found. instance: ' . $instance->id);

                return;
            }

            $this->case->case_title = $instance->case_title;
            $this->case->case_status = $instance->status === self::CASE_STATUS_ACTIVE ? 'IN_PROGRESS' : $instance->status;
            $this->case->request_tokens = CaseUtils::storeRequestTokens($token->getKey(), $this->case->request_tokens);
            $this->case->tasks = CaseUtils::storeTasks($token, $this->case->tasks);

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
        // If a sub-process is completed, do not update the case started status
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
            $this->case->processes = CaseUtils::storeProcesses($instance, $this->case->processes);
            $this->case->requests = CaseUtils::storeRequests($instance, $this->case->requests);

            $this->case->saveOrFail();
        } catch (\Exception $th) {
            \Log::error($th->getMessage());
        }
    }

    public static function syncCases(array $requestIds): array
    {
        $requests = ProcessRequest::with(['tokens', 'parentRequest'])->whereIn('id', $requestIds)->get();
        $successes = [];
        $errors = [];

        foreach ($requests as $instance) {
            try {
                $caseStarted = CaseStarted::updateOrCreate(
                    ['case_number' => $instance->case_number],
                    [
                        'user_id' => $instance->user_id,
                        'case_title' => $instance->case_title,
                        'case_title_formatted' => $instance->case_title_formatted,
                        'case_status' => $instance->status === 'ACTIVE' ? 'IN_PROGRESS' : $instance->status,
                        'processes' => CaseUtils::storeProcesses($instance, collect()),
                        'requests' => CaseUtils::storeRequests($instance, collect()),
                        'request_tokens' => CaseUtils::storeRequestTokens($instance->tokens->first()->getKey(), collect()),
                        'tasks' => CaseUtils::storeTasks($instance->tokens->first(), collect()),
                        'participants' => $instance->participants,
                        'initiated_at' => $instance->initiated_at,
                        'completed_at' => $instance->completed_at,
                    ],
                );

                foreach ($instance->tokens as $token) {
                    if ($token->element_type === 'task') {
                        self::syncCasesParticipated($caseStarted, $token);
                    }
                }

                $successes[] = $caseStarted->case_number;
            } catch (\Exception $e) {
                $errors[] = $instance->case_number . ' ' . $e->getMessage();
            }
        }

        return [
            'successes' => $successes,
            'errors' => $errors,
        ];
    }

    public static function syncCasesParticipated($caseStarted, $token)
    {
        CaseParticipated::updateOrCreate(
            [
                'case_number' => $caseStarted->case_number,
                'user_id' => $token->user_id,
            ],
            [
                'case_title' => $caseStarted->case_title,
                'case_title_formatted' => $caseStarted->case_title_formatted,
                'case_status' => $caseStarted->case_status,
                'processes' => CaseUtils::storeProcesses($token->processRequest, collect()),
                'requests' => CaseUtils::storeRequests($token->processRequest, collect()),
                'request_tokens' => CaseUtils::storeRequestTokens($token->getKey(), collect()),
                'tasks' => CaseUtils::storeTasks($token, collect()),
                'participants' => $caseStarted->participants,
                'initiated_at' => $caseStarted->initiated_at,
                'completed_at' => $caseStarted->completed_at,
            ]
        );
    }
}
