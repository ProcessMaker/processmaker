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
            Log::info('case number is required, method=create, instance=' . $instance->getKey());

            return;
        }

        if ($this->checkIfCaseStartedExist($instance->case_number)) {
            $this->updateSubProcesses($instance);

            return;
        }

        try {
            $processData = CaseUtils::extractData($instance->process, 'PROCESS');
            $requestData = CaseUtils::extractData($instance, 'REQUEST');
            $dataKeywords = CaseUtils::extractData($instance, 'KEYWORD');
            // Check the case status
            if (is_null($instance->last_stage_id)) {
                $instance->last_stage_name = $instance->case_status;
                $instance->progress = 50;
            }

            CaseStarted::create([
                'case_number' => $instance->case_number,
                'user_id' => $instance->user_id,
                'case_title' => $instance->case_title,
                'case_title_formatted' => $instance->case_title_formatted,
                'case_status' => CaseUtils::getStatus($instance->status),
                'processes' => CaseUtils::storeProcesses(collect(), $processData),
                'requests' => CaseUtils::storeRequests(collect(), $requestData),
                'request_tokens' => [],
                'tasks' => [],
                'participants' => [],
                'initiated_at' => $instance->initiated_at,
                'completed_at' => null,
                'keywords' => CaseUtils::getKeywords($dataKeywords),
                'last_stage_id' => $instance->last_stage_id,
                'last_stage_name' => $instance->last_stage_name,
                'progress' => 0,
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
            return;
        }

        try {
            $this->updateCaseWithSingleToken($instance, $token);
            $this->case->saveOrFail();
        } catch (\Exception $e) {
            Log::error('CaseException: ' . $e->getMessage());
            Log::error('CaseException: ' . $e->getTraceAsString());
        }
    }

    /**
     * Update the case started with a batch of tokens for performance optimization.
     *
     * @param ExecutionInstanceInterface $instance
     * @param array $tokenBatch Array of token data from batch processing
     * @return void
     */
    public function updateBatch(ExecutionInstanceInterface $instance, array $tokenBatch): void
    {
        if (!$this->checkIfCaseStartedExist($instance->case_number)) {
            return;
        }

        try {
            $this->updateCaseWithTokenBatch($instance, $tokenBatch);
            $this->case->saveOrFail();
        } catch (\Exception $e) {
            Log::error('CaseException (batch): ' . $e->getMessage());
            Log::error('CaseException (batch): ' . $e->getTraceAsString());
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
        if (is_null($instance->case_number)) {
            return;
        }

        // If a sub-process is completed, do not update the case started status
        if (!is_null($instance->parent_request_id)) {
            return;
        }

        try {
            $caseStatus = CaseUtils::getStatus($instance->status);

            $data = [
                'case_status' => $caseStatus,
            ];

            if (in_array($caseStatus, [CaseStatusConstants::COMPLETED, CaseStatusConstants::CANCELED])) {
                $data['completed_at'] = $instance->completed_at;
                $data['last_stage_name'] = $caseStatus;
                $data['progress'] = 100;
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
     * Update the case with a single token (used by update() method).
     *
     * @param ExecutionInstanceInterface $instance
     * @param TokenInterface $token
     * @return void
     */
    private function updateCaseWithSingleToken(ExecutionInstanceInterface $instance, TokenInterface $token): void
    {
        $taskData = CaseUtils::extractData($token, 'TASK');
        $dataKeywords = CaseUtils::extractData($instance, 'KEYWORD');

        $this->updateCaseCoreFields($instance, $dataKeywords);
        $this->case->last_stage_id = $token->stage_id;
        $this->case->last_stage_name = $token->stage_name;
        $this->case->progress = calculateProgressById($token->stage_id, $instance?->process?->stages);

        // Update collections for single token
        $this->updateRequestTokens([$token->getKey()]);
        $this->updateTasks([$taskData]);
        $this->updateParticipantsWithToken($token);
    }

    /**
     * Update the case with a batch of tokens (used by updateBatch() method).
     *
     * @param ExecutionInstanceInterface $instance
     * @param array $tokenBatch Array of token data from batch processing
     * @return void
     */
    private function updateCaseWithTokenBatch(ExecutionInstanceInterface $instance, array $tokenBatch): void
    {
        $dataKeywords = CaseUtils::extractData($instance, 'KEYWORD');
        
        // Get all tokens for this batch
        $tokenIds = collect($tokenBatch)->pluck('token_id')->toArray();
        $tokens = \ProcessMaker\Models\ProcessRequestToken::whereIn('id', $tokenIds)
            ->with(['user'])
            ->get()
            ->keyBy('id');

        // Find the latest token for stage information
        $latestToken = $this->findLatestToken($tokenBatch, $tokens);
        
        // Process batch data
        $batchData = $this->processTokenBatch($tokenBatch, $tokens);

        // Update case with batch data
        $this->updateCaseCoreFields($instance, $dataKeywords);
        
        // Use latest token for stage info
        if ($latestToken) {
            $this->case->last_stage_id = $latestToken->stage_id;
            $this->case->last_stage_name = $latestToken->stage_name;
            $this->case->progress = calculateProgressById($latestToken->stage_id, $instance?->process?->stages);
        }

        // Update collections for batch
        $this->updateRequestTokens($batchData['request_tokens']);
        $this->updateTasks($batchData['tasks']);
        $this->updateParticipantsWithUserIds($batchData['participants']);

        // Update case participated with latest data
        $this->caseParticipatedRepository->update($this->case);
    }

    /**
     * Update core case fields that are common for both single and batch updates.
     *
     * @param ExecutionInstanceInterface $instance
     * @param array $dataKeywords
     * @return void
     */
    private function updateCaseCoreFields(ExecutionInstanceInterface $instance, array $dataKeywords): void
    {
        $this->case->case_title = $instance->case_title;
        $this->case->case_title_formatted = $instance->case_title_formatted;
        $this->case->case_status = CaseUtils::getStatus($instance->status);
        $this->case->keywords = CaseUtils::getKeywords($dataKeywords);
    }

    /**
     * Process a batch of tokens and extract aggregated data.
     *
     * @param array $tokenBatch
     * @param \Illuminate\Support\Collection $tokens
     * @return array
     */
    private function processTokenBatch(array $tokenBatch, \Illuminate\Support\Collection $tokens): array
    {
        $allParticipants = collect();
        $allRequestTokens = collect($this->case->request_tokens ?? []);
        $allTasks = collect($this->case->tasks ?? []);

        foreach ($tokenBatch as $tokenData) {
            $token = $tokens->get($tokenData['token_id']);
            if (!$token) continue;

            $taskData = CaseUtils::extractData($token, 'TASK');
            
            // Collect unique request tokens
            $tokenKey = $token->getKey();
            if (!$allRequestTokens->contains($tokenKey)) {
                $allRequestTokens->push($tokenKey);
            }
            
            // Merge tasks data efficiently
            $this->mergeTaskData($allTasks, $taskData);

            // Collect participants
            $user = $token->user;
            if ($user?->id && !$allParticipants->contains($user->id)) {
                $allParticipants->push($user->id);
            }
        }

        return [
            'request_tokens' => $allRequestTokens->unique()->values()->toArray(),
            'tasks' => $allTasks->values()->toArray(),
            'participants' => $allParticipants->unique()->values()->toArray(),
        ];
    }

    /**
     * Find the latest token in a batch based on timestamp.
     *
     * @param array $tokenBatch
     * @param \Illuminate\Support\Collection $tokens
     * @return \ProcessMaker\Models\ProcessRequestToken|null
     */
    private function findLatestToken(array $tokenBatch, \Illuminate\Support\Collection $tokens): ?\ProcessMaker\Models\ProcessRequestToken
    {
        $latestToken = null;
        $latestTimestamp = 0;
        
        foreach ($tokenBatch as $tokenData) {
            if ($tokenData['timestamp'] > $latestTimestamp) {
                $latestTimestamp = $tokenData['timestamp'];
                $latestToken = $tokens->get($tokenData['token_id']);
            }
        }

        return $latestToken;
    }

    /**
     * Merge task data efficiently into the tasks collection.
     *
     * @param \Illuminate\Support\Collection $allTasks
     * @param array|null $taskData
     * @return void
     */
    private function mergeTaskData(\Illuminate\Support\Collection $allTasks, ?array $taskData): void
    {
        if (!$taskData || !is_array($taskData)) {
            return;
        }

        $existingTask = $allTasks->firstWhere('id', $taskData['id'] ?? null);
        if ($existingTask) {
            // Update existing task
            $index = $allTasks->search(function($task) use ($taskData) {
                return ($task['id'] ?? null) === ($taskData['id'] ?? null);
            });
            if ($index !== false) {
                $allTasks[$index] = array_merge($allTasks[$index], $taskData);
            }
        } else {
            // Add new task
            $allTasks->push($taskData);
        }
    }

    /**
     * Update request tokens collection.
     *
     * @param array $tokenKeys
     * @return void
     */
    private function updateRequestTokens(array $tokenKeys): void
    {
        $currentTokens = collect($this->case->request_tokens ?? []);
        $newTokens = collect($tokenKeys)->diff($currentTokens);
        
        if ($newTokens->isNotEmpty()) {
            $this->case->request_tokens = $currentTokens->merge($newTokens)->unique()->values()->toArray();
        }
    }

    /**
     * Update tasks collection.
     *
     * @param array $taskDataArray
     * @return void
     */
    private function updateTasks(array $taskDataArray): void
    {
        $currentTasks = collect($this->case->tasks ?? []);
        
        foreach ($taskDataArray as $taskData) {
            if (!$taskData || !is_array($taskData)) continue;
            
            $this->mergeTaskData($currentTasks, $taskData);
        }
        
        $this->case->tasks = $currentTasks->values()->toArray();
    }

    /**
     * Update participants with a single token.
     *
     * @param TokenInterface $token
     * @return void
     */
    private function updateParticipantsWithToken(TokenInterface $token): void
    {
        $user = $token->user;
        $currentParticipants = collect($this->case->participants ?? []);
        
        if ($user?->id && !$currentParticipants->contains($user->id)) {
            $this->case->participants = $currentParticipants->merge([$user->id])->unique()->values()->toArray();
            $this->caseParticipatedRepository->create($this->case, $user->id);
        }

        $this->caseParticipatedRepository->update($this->case);
    }

    /**
     * Update participants with a list of user IDs.
     *
     * @param array $participantUserIds
     * @return void
     */
    private function updateParticipantsWithUserIds(array $participantUserIds): void
    {
        $currentParticipants = collect($this->case->participants ?? []);
        $newParticipants = collect($participantUserIds)->diff($currentParticipants);
        
        if ($newParticipants->isNotEmpty()) {
            $this->case->participants = $currentParticipants->merge($newParticipants)->unique()->values()->toArray();
            
            // Create case participated for all new participants
            foreach ($newParticipants as $userId) {
                $this->caseParticipatedRepository->create($this->case, $userId);
            }
        }
    }

    /**
     * Update the participants of the case started.
     * This is a legacy method that uses the original CaseUtils implementation.
     * Kept for compatibility.
     *
     * @param TokenInterface $token
     * @return void
     */
    private function updateParticipants(TokenInterface $token): void
    {
        // Redirect to the new optimized method
        $this->updateParticipantsWithToken($token);
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
            $processData = CaseUtils::extractData($instance->process, 'PROCESS');
            $requestData = CaseUtils::extractData($instance, 'REQUEST');

            // Store the sub-processes and requests
            $this->case->processes = CaseUtils::storeProcesses($this->case->processes, $processData);
            $this->case->requests = CaseUtils::storeRequests($this->case->requests, $requestData);

            $this->case->saveOrFail();

            $this->caseParticipatedRepository->update($this->case);
        } catch (\Exception $e) {
            Log::error('CaseException: ' . $e->getMessage());
            Log::error('CaseException: ' . $e->getTraceAsString());
        }
    }
}
