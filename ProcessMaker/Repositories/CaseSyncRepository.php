<?php

namespace ProcessMaker\Repositories;

use Illuminate\Support\Collection;
use ProcessMaker\Constants\CaseStatusConstants;
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

                $processData = CaseUtils::extractData($instance->process, [
                    'id' => 'id',
                    'name' => 'name',
                ]);

                $requestData = CaseUtils::extractData($instance, [
                    'id' => 'id',
                    'name' => 'name',
                    'parent_request_id' => 'parentRequest.id',
                ]);

                $caseStartedProcesses = CaseUtils::storeProcesses(collect(), $processData);
                $caseStartedRequests = CaseUtils::storeRequests(collect(), $requestData);
                $caseStartedRequestTokens = collect();
                $caseStartedTasks = collect();
                $participants = $instance->participants->map->only('id', 'fullName', 'title', 'avatar');
                $status = $instance->status === CaseStatusConstants::ACTIVE ? CaseStatusConstants::IN_PROGRESS : $instance->status;

                $caseParticipatedData = self::prepareCaseStartedData($instance, $status, $participants);

                self::processTokens($instance, $caseParticipatedData, $caseStartedRequestTokens, $caseStartedTasks);

                self::processChildRequests($instance, $caseParticipatedData, $caseStartedProcesses, $caseStartedRequests, $participants, $caseStartedRequestTokens, $caseStartedTasks);

                $dataKeywords = CaseUtils::extractData($instance, [
                    'case_number' => 'case_number',
                    'case_title' => 'case_title',
                ]);

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
     * Prepare the case started data.
     *
     * @param ProcessRequest $instance
     * @param string $status
     * @param Collection $participants
     * @return array
     */
    private static function prepareCaseStartedData($instance, $status, $participants)
    {
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
        foreach ($instance->tokens as $token) {
            if (in_array($token->element_type, CaseUtils::ALLOWED_REQUEST_TOKENS)) {
                $processData = [
                    'id' => $instance->process->id,
                    'name' => $instance->process->name,
                ];

                $requestData = [
                    'id' => $instance->id,
                    'name' => $instance->name,
                    'parent_request_id' => $instance?->parentRequest?->id,
                ];

                $taskData = [
                    'id' => $token->getKey(),
                    'element_id' => $token->element_id,
                    'name' => $token->element_name,
                    'process_id' => $token->process_id,
                    'element_type' => $token->element_type,
                ];

                $caseParticipatedData['processes'] = CaseUtils::storeProcesses($caseParticipatedData['processes'], $processData);
                $caseParticipatedData['requests'] = CaseUtils::storeRequests($caseParticipatedData['requests'], $requestData);
                $caseParticipatedData['request_tokens'] = CaseUtils::storeRequestTokens($caseParticipatedData['request_tokens'], $token->getKey());
                $caseParticipatedData['tasks'] = CaseUtils::storeTasks($caseParticipatedData['tasks'], $taskData);

                $caseStartedRequestTokens = CaseUtils::storeRequestTokens($caseStartedRequestTokens, $token->getKey());
                $caseStartedTasks = CaseUtils::storeTasks($caseStartedTasks, $taskData);

                self::syncCasesParticipated($instance->case_number, $token->user_id, $caseParticipatedData);
            }
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
            $processData = CaseUtils::extractData($subProcess->process, [
                'id' => 'id',
                'name' => 'name',
            ]);

            $requestData = CaseUtils::extractData($subProcess, [
                'id' => 'id',
                'name' => 'name',
                'parent_request_id' => 'parentRequest.id',
            ]);

            $caseParticipatedData['processes'] = CaseUtils::storeProcesses(collect(), $processData);
            $caseParticipatedData['requests'] = CaseUtils::storeRequests(collect(), $requestData);
            $caseParticipatedData['request_tokens'] = collect();
            $caseParticipatedData['tasks'] = collect();

            $caseStartedProcesses = CaseUtils::storeProcesses($caseStartedProcesses, $processData);
            $caseStartedRequests = CaseUtils::storeRequests($caseStartedRequests, $requestData);

            $participants = $participants
                ->merge($subProcess->participants->map->only('id', 'fullName', 'title', 'avatar'))
                ->unique('id')
                ->values();

            foreach ($subProcess->tokens as $spToken) {
                $taskData = [
                    'id' => $spToken->getKey(),
                    'element_id' => $spToken->element_id,
                    'name' => $spToken->element_name,
                    'process_id' => $spToken->process_id,
                    'element_type' => $spToken->element_type,
                ];

                if (in_array($spToken->element_type, CaseUtils::ALLOWED_REQUEST_TOKENS)) {
                    $caseParticipatedData['processes'] = CaseUtils::storeProcesses($caseParticipatedData['processes'], $processData);
                    $caseParticipatedData['requests'] = CaseUtils::storeRequests($caseParticipatedData['requests'], $requestData);
                    $caseParticipatedData['request_tokens'] = CaseUtils::storeRequestTokens($caseParticipatedData['request_tokens'], $spToken->getKey());
                    $caseParticipatedData['tasks'] = CaseUtils::storeTasks($caseParticipatedData['tasks'], $taskData);

                    $caseStartedRequestTokens = CaseUtils::storeRequestTokens($caseStartedRequestTokens, $spToken->getKey());
                    $caseStartedTasks = CaseUtils::storeTasks($caseStartedTasks, $taskData);

                    self::syncCasesParticipated($instance->case_number, $spToken->user_id, $caseParticipatedData);
                }
            }
        }
    }

    /**
     * Sync the cases participated.
     *
     * @param CaseStarted $caseStarted
     * @param TokenInterface $token
     * @return void
     */
    private static function syncCasesParticipated($caseNumber, $userId, $data)
    {
        CaseParticipated::updateOrCreate(
            [
                'case_number' => $caseNumber,
                'user_id' => $userId,
            ],
            $data,
        );
    }
}
