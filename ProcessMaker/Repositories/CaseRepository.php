<?php

namespace ProcessMaker\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Constants\CaseStatusConstants;
use ProcessMaker\Contracts\CaseRepositoryInterface;
use ProcessMaker\Models\CaseStarted;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;

class CaseRepository implements CaseRepositoryInterface
{
    /**
     * @var CaseParticipatedRepository
     */
    protected CaseParticipatedRepository $caseParticipatedRepository;

    /**
     * This property is used to store an instance of `CaseStarted`
     * when a case started is updated.
     * @var CaseStarted|null
     */
    protected ?CaseStarted $case;

    public function __construct()
    {
        $this->caseParticipatedRepository = new CaseParticipatedRepository();
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
            Log::error('case number is required, method=create, instance=' . $instance->getKey());

            return;
        }

        if ($this->checkIfCaseStartedExist($instance->case_number)) {
            $this->updateSubProcesses($instance);

            return;
        }

        try {
            $processData = CaseUtils::extractData($instance->process, [
                'id' => 'id',
                'name' => 'name',
            ]);

            $requestData = CaseUtils::extractData($instance, [
                'id' => 'id',
                'name' => 'name',
                'parent_request_id' => 'parentRequest.id',
            ]);

            $dataKeywords = CaseUtils::extractData($instance, [
                'case_number' => 'case_number',
                'case_title' => 'case_title',
            ]);

            CaseStarted::create([
                'case_number' => $instance->case_number,
                'user_id' => $instance->user_id,
                'case_title' => $instance->case_title,
                'case_title_formatted' => $instance->case_title_formatted,
                'case_status' => $instance->status === CaseStatusConstants::ACTIVE ? CaseStatusConstants::IN_PROGRESS : $instance->status,
                'processes' => CaseUtils::storeProcesses(collect(), $processData),
                'requests' => CaseUtils::storeRequests(collect(), $requestData),
                'request_tokens' => [],
                'tasks' => [],
                'participants' => [],
                'initiated_at' => $instance->initiated_at,
                'completed_at' => null,
                'keywords' => CaseUtils::getKeywords($dataKeywords),
            ]);
        } catch (\Exception $e) {
            Log::error('CaseException: ' . $e->getMessage());
            Log::error('CaseException: ' . $e->getTraceAsString());
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
        if (!$this->checkIfCaseStartedExist($instance->case_number)) {
            Log::error('case started not found, method=update, instance=' . $instance->getKey());

            return;
        }

        try {
            $taskData = CaseUtils::extractData($token, [
                'id' => 'id',
                'element_id' => 'element_id',
                'name' => 'element_name',
                'process_id' => 'process_id',
                'element_type' => 'element_type',
            ]);

            $dataKeywords = CaseUtils::extractData($instance, [
                'case_number' => 'case_number',
                'case_title' => 'case_title',
            ]);

            $this->case->case_title = $instance->case_title;
            $this->case->case_status = $instance->status === CaseStatusConstants::ACTIVE ? CaseStatusConstants::IN_PROGRESS : $instance->status;
            $this->case->request_tokens = CaseUtils::storeRequestTokens($this->case->request_tokens, $token->getKey());
            $this->case->tasks = CaseUtils::storeTasks($this->case->tasks, $taskData);
            $this->case->keywords = CaseUtils::getKeywords($dataKeywords);

            $this->updateParticipants($token);

            $this->case->saveOrFail();
        } catch (\Exception $e) {
            Log::error('CaseException: ' . $e->getMessage());
            Log::error('CaseException: ' . $e->getTraceAsString());
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

            if ($instance->status === CaseStatusConstants::COMPLETED) {
                $data['completed_at'] = Carbon::now();
            }

            // Update the case started and case participated
            CaseStarted::where('case_number', $instance->case_number)->update($data);
            $this->caseParticipatedRepository->updateStatus($instance->case_number, $data);
        } catch (\Exception $e) {
            Log::error('CaseException: ' . $e->getMessage());
            Log::error('CaseException: ' . $e->getTraceAsString());
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

        $participantExists = $this->case->participants->contains($user->id);

        if (!$participantExists) {
            $this->case->participants->push($user->id);

            $this->caseParticipatedRepository->create($this->case, $token);
        }

        $this->caseParticipatedRepository->update($this->case, $token);
    }

    /**
     * Check if the case started exist.
     *
     * @param int|null $caseNumber
     * @return bool
     */
    private function checkIfCaseStartedExist(int | null $caseNumber): bool
    {
        if (is_null($caseNumber)) {
            return false;
        }

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
            $processData = CaseUtils::extractData($instance->process, [
                'id' => 'id',
                'name' => 'name',
            ]);

            $requestData = CaseUtils::extractData($instance, [
                'id' => 'id',
                'name' => 'name',
                'parent_request_id' => 'parentRequest.id',
            ]);

            // Store the sub-processes and requests
            $this->case->processes = CaseUtils::storeProcesses($this->case->processes, $processData);
            $this->case->requests = CaseUtils::storeRequests($this->case->requests, $requestData);

            $this->case->saveOrFail();
        } catch (\Exception $e) {
            Log::error('CaseException: ' . $e->getMessage());
            Log::error('CaseException: ' . $e->getTraceAsString());
        }
    }
}