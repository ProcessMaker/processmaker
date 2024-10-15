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

                $csProcesses = CaseUtils::storeProcesses($instance, collect());
                $csRequests = CaseUtils::storeRequests($instance, collect());
                $csRequestTokens = collect();
                $csTasks = collect();
                $participants = $instance->participants->map->only('id', 'fullName', 'title', 'avatar');
                $status = $instance->status === CaseStatusConstants::ACTIVE ? CaseStatusConstants::IN_PROGRESS : $instance->status;

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
        return [
            'case_title' => $instance->case_title,
            'case_title_formatted' => $instance->case_title_formatted,
            'case_status' => $status,
            'processes' => CaseUtils::storeProcesses($instance, collect()),
            'requests' => CaseUtils::storeRequests($instance, collect()),
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
                $cpData['processes'] = CaseUtils::storeProcesses($instance, $cpData['processes']);
                $cpData['requests'] = CaseUtils::storeRequests($instance, $cpData['requests']);
                $cpData['request_tokens'] = CaseUtils::storeRequestTokens($token->getKey(), $cpData['request_tokens']);
                $cpData['tasks'] = CaseUtils::storeTasks($token, $cpData['tasks']);

                $csRequestTokens = CaseUtils::storeRequestTokens($token->getKey(), $csRequestTokens);
                $csTasks = CaseUtils::storeTasks($token, $csTasks);

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
            $cpData['processes'] = CaseUtils::storeProcesses($subProcess, collect());
            $cpData['requests'] = CaseUtils::storeRequests($subProcess, collect());
            $cpData['request_tokens'] = collect();
            $cpData['tasks'] = collect();

            $csProcesses = CaseUtils::storeProcesses($subProcess, $csProcesses);
            $csRequests = CaseUtils::storeRequests($subProcess, $csRequests);

            $participants = $participants
                ->merge($subProcess->participants->map->only('id', 'fullName', 'title', 'avatar'))
                ->unique('id')
                ->values();

            foreach ($subProcess->tokens as $spToken) {
                if (in_array($spToken->element_type, CaseUtils::ALLOWED_REQUEST_TOKENS)) {
                    $cpData['processes'] = CaseUtils::storeProcesses($subProcess, $cpData['processes']);
                    $cpData['requests'] = CaseUtils::storeRequests($subProcess, $cpData['requests']);
                    $cpData['request_tokens'] = CaseUtils::storeRequestTokens($spToken->getKey(), $cpData['request_tokens']);
                    $cpData['tasks'] = CaseUtils::storeTasks($spToken, $cpData['tasks']);

                    $csRequestTokens = CaseUtils::storeRequestTokens($spToken->getKey(), $csRequestTokens);
                    $csTasks = CaseUtils::storeTasks($spToken, $csTasks);

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
