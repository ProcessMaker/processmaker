<?php

namespace ProcessMaker\InboxRules;

use ProcessMaker\Models\InboxRule;
use ProcessMaker\Models\ProcessRequestToken;

/**
 * This Class is used in 2 ways
 * 1. After a task is assigned, it checks to see if it matches any active InboxRules in the system and returns the InboxRule models.
 * 2. The `get` method returns all tasks that match a given InboxRule
 */
class MatchingTasks
{
    public function matchingInboxRules(ProcessRequestToken $task) : array
    {
        $matchingInboxRules = [];

        if ($task && $task->user_id) {
            //The Foreach has only inbox rules ACTIVE=true and user_id = $task->user_id
            foreach ($this->queryInboxRules($task) as $rule) {
                if ($this->isEndDatePast($rule)) {
                    continue;
                }

                if ($rule->saved_search_id !== null) {
                    if ($this->matchesResultInSavedSearch($rule, $task)) {
                        $matchingInboxRules[] = $rule;
                    }
                }

                if (
                    $rule->process_request_token_id !== null &&
                    $task->process_id == $rule->task->process_id &&
                    $task->element_id == $rule->task->element_id
                ) {
                    $matchingInboxRules[] = $rule;
                }
            }
            return $matchingInboxRules;
        } else {
            return [];
        }
    }

    public function get(InboxRule $inboxRule) : array
    {
        if ($savedSearch = $inboxRule->savedSearch) {
            return $savedSearch->query->get();
        }

        if ($task = $inboxRule->task) {
            return ProcessRequestToken::where([
                'process_id' => $task->process_id,
                'element_id' => $task->element_id,
                'user_id' => $inboxRule->user_id,
                'status' => 'ACTIVE',
            ])->get();
        }
    }

    public function matchesResultInSavedSearch($rule, $task)
    {
        return $rule->savedSearch->query
                        ->where('process_request_tokens.user_id', $task->user_id)
                        ->where('process_request_tokens.id', $task->id)
                        ->exists();
    }

    public function queryInboxRules($task)
    {
        return InboxRule::where('active', true)
            ->where('user_id', $task->user_id)
            ->get();
    }

    public function isEndDatePast($rule) {
        if ($rule->end_date && $rule->end_date->isPast()) {
            return true;
        }
        return false;
    }
}
