<?php

namespace ProcessMaker\Repositories;

use Illuminate\Support\Collection;
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

                $processData = [
                    'id' => $instance->process->id,
                    'name' => $instance->process->name,
                ];

                $requestData = [
                    'id' => $instance->id,
                    'name' => $instance->name,
                    'parent_request_id' => $instance?->parentRequest?->id,
                ];

                $csProcesses = CaseUtils::storeProcesses(collect(), $processData);
                $csRequests = CaseUtils::storeRequests(collect(), $requestData);
                $csRequestTokens = collect();
                $csTasks = collect();
                $participants = $instance->participants->map->only('id', 'fullName', 'title', 'avatar');
                $status = $instance->status === CaseRepository::CASE_STATUS_ACTIVE ? 'IN_PROGRESS' : $instance->status;

                $cpData = self::prepareCaseStartedData($instance, $status, $participants);

                self::processTokens($instance, $cpData, $csRequestTokens, $csTasks);

                self::processChildRequests($instance, $cpData, $csProcesses, $csRequests, $participants, $csRequestTokens, $csTasks);

                $caseStarted = CaseStarted::updateOrCreate(
                    ['case_number' => $instance->case_number],
                    [
                        'user_id' => $instance->user_id,
                        'case_title' => $instance->case_title,
                        'case_title_formatted' => $instance->case_title_formatted,
                        'case_status' => $status,
                        'processes' => $csProcesses,
                        'requests' => $csRequests,
                        'request_tokens' => $csRequestTokens,
                        'tasks' => $csTasks,
                        'participants' => $participants,
                        'initiated_at' => $instance->initiated_at,
                        'completed_at' => $instance->completed_at,
                        'keywords' => CaseUtils::getCaseNumberByKeywords($instance->case_number) . ' ' . $instance->case_title,
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
        $processData = [
            'id' => $instance->process->id,
            'name' => $instance->process->name,
        ];

        $requestData = [
            'id' => $instance->id,
            'name' => $instance->name,
            'parent_request_id' => $instance?->parentRequest?->id,
        ];

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
            'keywords' => CaseUtils::getCaseNumberByKeywords($instance->case_number) . ' ' . $instance->case_title,
        ];
    }

    /**
     * Process the tokens.
     *
     * @param ProcessRequest $instance
     * @param array $cpData
     * @param Collection $csRequestTokens
     * @param Collection $csTasks
     * @return void
     */
    private static function processTokens($instance, &$cpData, &$csRequestTokens, &$csTasks)
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

                $cpData['processes'] = CaseUtils::storeProcesses($cpData['processes'], $processData);
                $cpData['requests'] = CaseUtils::storeRequests($cpData['requests'], $requestData);
                $cpData['request_tokens'] = CaseUtils::storeRequestTokens($cpData['request_tokens'], $token->getKey());
                $cpData['tasks'] = CaseUtils::storeTasks($cpData['tasks'], $taskData);

                $csRequestTokens = CaseUtils::storeRequestTokens($csRequestTokens, $token->getKey());
                $csTasks = CaseUtils::storeTasks($csTasks, $taskData);

                self::syncCasesParticipated($instance->case_number, $token->user_id, $cpData);
            }
        }
    }

    /**
     * Process the child requests.
     *
     * @param ProcessRequest $instance
     * @param array $cpData
     * @param Collection $csProcesses
     * @param Collection $csRequests
     * @param Collection $participants
     * @param Collection $csRequestTokens
     * @param Collection $csTasks
     * @return void
     */
    private static function processChildRequests(
        $instance, &$cpData, &$csProcesses, &$csRequests, &$participants, &$csRequestTokens, &$csTasks
    ) {
        foreach ($instance->childRequests as $subProcess) {
            $processData = [
                'id' => $subProcess->process->id,
                'name' => $subProcess->process->name,
            ];

            $requestData = [
                'id' => $subProcess->id,
                'name' => $subProcess->name,
                'parent_request_id' => $subProcess?->parentRequest?->id,
            ];

            $cpData['processes'] = CaseUtils::storeProcesses(collect(), $processData);
            $cpData['requests'] = CaseUtils::storeRequests(collect(), $requestData);
            $cpData['request_tokens'] = collect();
            $cpData['tasks'] = collect();

            $csProcesses = CaseUtils::storeProcesses($csProcesses, $processData);
            $csRequests = CaseUtils::storeRequests($csRequests, $requestData);

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
                    $cpData['processes'] = CaseUtils::storeProcesses($cpData['processes'], $processData);
                    $cpData['requests'] = CaseUtils::storeRequests($cpData['requests'], $requestData);
                    $cpData['request_tokens'] = CaseUtils::storeRequestTokens($cpData['request_tokens'], $spToken->getKey());
                    $cpData['tasks'] = CaseUtils::storeTasks($cpData['tasks'], $taskData);

                    $csRequestTokens = CaseUtils::storeRequestTokens($csRequestTokens, $spToken->getKey());
                    $csTasks = CaseUtils::storeTasks($csTasks, $taskData);

                    self::syncCasesParticipated($instance->case_number, $spToken->user_id, $cpData);
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
