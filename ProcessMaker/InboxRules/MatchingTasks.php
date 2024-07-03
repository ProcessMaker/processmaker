<?php

namespace ProcessMaker\InboxRules;

use Illuminate\Support\Collection;
use ProcessMaker\Models\InboxRule;
use ProcessMaker\Models\ProcessRequestToken;

/**
 * This Class is used in 2 ways
 * 1. After a task is assigned, it checks to see if it matches any active
 *    InboxRules in the system and returns the InboxRule models.
 * 2. The `get` method returns all tasks that match a given InboxRule
 */

class MatchingTasks
{
    /**
     * @param  \ProcessMaker\Models\ProcessRequestToken  $task
     *
     * @return array
     */
    public function matchingInboxRules(ProcessRequestToken $task): array
    {
        if (!$task || !$task->user_id) {
            return [];
        }

        $matchingInboxRules = [];
        //The Foreach has only inbox rules ACTIVE=true and user_id = $task->user_id
        foreach ($this->queryInboxRules($task) as $rule) {
            if ($this->isEndDatePast($rule)) {
                continue;
            }

            if ($this->matchesSavedSearch($rule, $task)) {
                $matchingInboxRules[] = $rule;
            }
        }

        return $matchingInboxRules;
    }

    /**
     * @param $rule
     *
     * @return bool
     */
    public function shouldSkipRule($rule): bool
    {
        return $this->isEndDatePast($rule);
    }

    /**
     * @param $rule
     * @param $task
     *
     * @return bool
     */
    public function matchesSavedSearch($rule, $task): bool
    {
        return $rule->saved_search_id !== null && $this->matchesResultInSavedSearch($rule, $task);
    }

    /**
     * @param  \ProcessMaker\Models\InboxRule  $inboxRule
     *
     * @return \Illuminate\Support\Collection
     */
    public function get(InboxRule $inboxRule) : Collection
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

    /**
     * @param $rule
     * @param $task
     *
     * @return mixed
     */
    public function matchesResultInSavedSearch($rule, $task)
    {
        return $rule->savedSearch->query
                        ->where('process_request_tokens.user_id', $task->user_id)
                        ->where('process_request_tokens.id', $task->id)
                        ->exists();
    }

    /**
     * @param $task
     *
     * @return mixed
     */
    public function queryInboxRules($task)
    {
        return InboxRule::where('active', true)
            ->where('user_id', $task->user_id)
            ->get();
    }

    /**
     * @param $rule
     *
     * @return bool
     */
    public function isEndDatePast($rule) : bool
    {
        if ($rule->end_date && $rule->end_date->isPast()) {
            return true;
        }

        return false;
    }
}
