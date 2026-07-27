<?php

namespace ProcessMaker\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\CaseParticipated;
use ProcessMaker\Models\CaseStarted;
use ProcessMaker\Models\ProcessRequest;

class CaseSyncRepository
{
    /**
     * Sync the cases started.
     *
     * @param array $requestIds
     * @return array
     */
    public static function syncCases(array $requestIds): array
    {
        $requests = ProcessRequest::with([
            'tokens', 'process', 'childRequests.process', 'childRequests.tokens', 'participants',
        ])
        ->whereIn('id', $requestIds)
        ->get();

        $successes = [];
        $errors = [];

        foreach ($requests as $instance) {
            try {
                if (!is_null($instance->parent_request_id)) {
                    continue;
                }

                $processData = CaseUtils::extractData($instance->process, 'PROCESS');
                $requestData = CaseUtils::extractData($instance, 'REQUEST');

                $caseStartedProcesses = CaseUtils::storeProcesses(collect(), $processData);
                $caseStartedRequests = CaseUtils::storeRequests(collect(), $requestData);
                $caseStartedRequestTokens = collect();
                $caseStartedTasks = collect();
                $participants = self::getParticipantsFromInstance($instance);
                $status = CaseUtils::getStatus($instance->status);

                $caseParticipatedData = self::prepareCaseStartedData($instance, $status, $participants);

                self::processTokens($instance, $caseParticipatedData, $caseStartedRequestTokens, $caseStartedTasks);

                self::processChildRequests($instance, $caseParticipatedData, $caseStartedProcesses, $caseStartedRequests, $participants, $caseStartedRequestTokens, $caseStartedTasks);

                $dataKeywords = CaseUtils::extractData($instance, 'KEYWORD');

                $caseStarted = CaseStarted::updateOrCreate(
                    ['case_number' => $instance->case_number],
                    [
                        'user_id' => $instance->user_id,
                        'case_title' => $instance->case_title,
                        'case_title_formatted' => $instance->case_title_formatted,
                        'case_status' => $status,
                        'processes' => $caseStartedProcesses,
                        'requests' => $caseStartedRequests,
                        'request_tokens' => $caseStartedRequestTokens,
                        'tasks' => $caseStartedTasks,
                        'participants' => $participants,
                        'initiated_at' => $instance->initiated_at,
                        'completed_at' => $instance->completed_at,
                        'keywords' => CaseUtils::getKeywords($dataKeywords),
                    ],
                );
                // Copy case-level columns from cases_started to cases_participated, except
                // user_id and tasks. Tasks are user-scoped and applied separately below.
                $sql = "UPDATE cases_participated AS cp
                INNER JOIN cases_started AS cs ON cp.case_number = cs.case_number
                SET
                    cp.case_title = cs.case_title,
                    cp.case_title_formatted = cs.case_title_formatted,
                    cp.case_status = cs.case_status,
                    cp.processes = cs.processes,
                    cp.requests = cs.requests,
                    cp.request_tokens = cs.request_tokens,
                    cp.participants = cs.participants,
                    cp.initiated_at = cs.initiated_at,
                    cp.completed_at = cs.completed_at,
                    cp.keywords = cs.keywords
                WHERE cp.case_number = '{$instance->case_number}';
                ";
                DB::statement($sql);

                self::syncParticipatedTasks($instance->case_number, $caseStartedTasks);

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

    /**
     * Get the participants from the instance.
     *
     * @param ProcessRequest|\Illuminate\Database\Eloquent\Builder $instance
     * @return Collection
     */
    private static function getParticipantsFromInstance(ProcessRequest|\Illuminate\Database\Eloquent\Builder $instance)
    {
        return $instance->participants()
            ->whereIn('process_request_tokens.element_type', CaseUtils::ALLOWED_ELEMENT_TYPES)
            ->pluck('process_request_tokens.user_id');
    }

    /**
     * Prepare the case started data.
     *
     * @param ProcessRequest $instance
     * @param string $status
     * @param Collection $participants
     * @return array
     */
    private static function prepareCaseStartedData($instance, $status, $participants)
    {
        $processData = CaseUtils::extractData($instance->process, 'PROCESS');
        $requestData = CaseUtils::extractData($instance, 'REQUEST');
        $dataKeywords = CaseUtils::extractData($instance, 'KEYWORD');

        return [
            'case_title' => $instance->case_title,
            'case_title_formatted' => $instance->case_title_formatted,
            'case_status' => $status,
            'processes' => CaseUtils::storeProcesses(collect(), $processData),
            'requests' => CaseUtils::storeRequests(collect(), $requestData),
            'request_tokens' => collect(),
            'tasks' => collect(),
            'participants' => $participants,
            'initiated_at' => $instance->initiated_at,
            'completed_at' => $instance->completed_at,
            'keywords' => CaseUtils::getKeywords($dataKeywords),
        ];
    }

    /**
     * Process the tokens.
     *
     * @param ProcessRequest $instance
     * @param array $caseParticipatedData
     * @param Collection $caseStartedRequestTokens
     * @param Collection $caseStartedTasks
     * @return void
     */
    private static function processTokens($instance, &$caseParticipatedData, &$caseStartedRequestTokens, &$caseStartedTasks)
    {
        $tokens = $instance->tokens()->select('id', 'element_id', 'element_name', 'process_id', 'element_type', 'status', 'user_id')
            ->whereIn('element_type', CaseUtils::ALLOWED_ELEMENT_TYPES)
            ->get();
        foreach ($tokens as $token) {
            $processData = [
                'id' => $instance->process->id,
                'name' => $instance->process->name,
            ];

            $requestData = [
                'id' => $instance->id,
                'name' => $instance->name,
                'parent_request_id' => $instance?->parent_request_id,
            ];

            $taskData = [
                'id' => $token->getKey(),
                'element_id' => $token->element_id,
                'name' => $token->element_name,
                'process_id' => $token->process_id,
                'element_type' => $token->element_type,
                'status' => $token->status,
                'user_id' => $token->user_id,
            ];

            $caseParticipatedData['processes'] = CaseUtils::storeProcesses($caseParticipatedData['processes'], $processData);
            $caseParticipatedData['requests'] = CaseUtils::storeRequests($caseParticipatedData['requests'], $requestData);
            $caseParticipatedData['request_tokens'] = CaseUtils::storeRequestTokens($caseParticipatedData['request_tokens'], $token->getKey());
            $caseParticipatedData['tasks'] = CaseUtils::storeTasks($caseParticipatedData['tasks'], $taskData);

            $caseStartedRequestTokens = CaseUtils::storeRequestTokens($caseStartedRequestTokens, $token->getKey());
            $caseStartedTasks = CaseUtils::storeTasks($caseStartedTasks, $taskData);

            self::syncCasesParticipated(
                $instance->case_number,
                $token->user_id,
                self::participatedDataForUser($caseParticipatedData, $token->user_id)
            );
        }
    }

    /**
     * Process the child requests.
     *
     * @param ProcessRequest $instance
     * @param array $caseParticipatedData
     * @param Collection $caseStartedProcesses
     * @param Collection $caseStartedRequests
     * @param Collection $participants
     * @param Collection $caseStartedRequestTokens
     * @param Collection $caseStartedTasks
     * @return void
     */
    private static function processChildRequests(
        $instance, &$caseParticipatedData, &$caseStartedProcesses, &$caseStartedRequests, &$participants, &$caseStartedRequestTokens, &$caseStartedTasks
    ) {
        foreach ($instance->childRequests as $subProcess) {
            $processData = CaseUtils::extractData($subProcess->process, 'PROCESS');
            $requestData = CaseUtils::extractData($subProcess, 'REQUEST');

            $caseParticipatedData['processes'] = CaseUtils::storeProcesses(collect(), $processData);
            $caseParticipatedData['requests'] = CaseUtils::storeRequests(collect(), $requestData);
            $caseParticipatedData['request_tokens'] = collect();
            $caseParticipatedData['tasks'] = collect();

            $caseStartedProcesses = CaseUtils::storeProcesses($caseStartedProcesses, $processData);
            $caseStartedRequests = CaseUtils::storeRequests($caseStartedRequests, $requestData);

            $participants = $participants
                ->merge(self::getParticipantsFromInstance($subProcess))
                ->unique()
                ->values();

            foreach ($subProcess->tokens as $spToken) {
                $taskData = [
                    'id' => $spToken->getKey(),
                    'element_id' => $spToken->element_id,
                    'name' => $spToken->element_name,
                    'process_id' => $spToken->process_id,
                    'element_type' => $spToken->element_type,
                    'status' => $spToken->status,
                    'user_id' => $spToken->user_id,
                ];

                if (in_array($spToken->element_type, CaseUtils::ALLOWED_ELEMENT_TYPES)) {
                    $caseParticipatedData['processes'] = CaseUtils::storeProcesses($caseParticipatedData['processes'], $processData);
                    $caseParticipatedData['requests'] = CaseUtils::storeRequests($caseParticipatedData['requests'], $requestData);
                    $caseParticipatedData['request_tokens'] = CaseUtils::storeRequestTokens($caseParticipatedData['request_tokens'], $spToken->getKey());
                    $caseParticipatedData['tasks'] = CaseUtils::storeTasks($caseParticipatedData['tasks'], $taskData);

                    $caseStartedRequestTokens = CaseUtils::storeRequestTokens($caseStartedRequestTokens, $spToken->getKey());
                    $caseStartedTasks = CaseUtils::storeTasks($caseStartedTasks, $taskData);

                    self::syncCasesParticipated(
                        $instance->case_number,
                        $spToken->user_id,
                        self::participatedDataForUser($caseParticipatedData, $spToken->user_id)
                    );
                }
            }
        }
    }

    /**
     * Sync the cases participated.
     *
     * @param int|string $caseNumber
     * @param int|null $userId
     * @param array $data
     * @return void
     */
    private static function syncCasesParticipated($caseNumber, $userId, $data)
    {
        if (is_null($userId)) {
            return;
        }

        CaseParticipated::updateOrCreate(
            [
                'case_number' => $caseNumber,
                'user_id' => $userId,
            ],
            $data,
        );
    }

    /**
     * Build participated case data with tasks scoped to a specific user.
     *
     * @param array $data
     * @param int|null $userId
     * @return array
     */
    private static function participatedDataForUser(array $data, $userId): array
    {
        if (is_null($userId)) {
            return $data;
        }

        $data['tasks'] = CaseUtils::filterTasksByUser($data['tasks'] ?? collect(), $userId);

        return $data;
    }

    /**
     * Apply user-scoped task lists to all participated rows for a case.
     *
     * @param int|string $caseNumber
     * @param Collection $caseStartedTasks
     * @return void
     */
    private static function syncParticipatedTasks($caseNumber, Collection $caseStartedTasks): void
    {
        $participants = CaseParticipated::where('case_number', $caseNumber)->get();

        foreach ($participants as $participant) {
            $participant->update([
                'tasks' => CaseUtils::filterTasksByUser($caseStartedTasks, $participant->user_id),
            ]);
        }
    }
}
