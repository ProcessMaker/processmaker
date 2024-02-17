<?php

namespace ProcessMaker\InboxRules;

use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\InboxRule;

class MatchingTasks
{
    protected $inboxRule;

    public function __construct(InboxRule $inboxRule)
    {
        $this->inboxRule = $inboxRule;
    }

    public function check(ProcessRequestToken $task)
    {
        if ($task && $task->user_id) {

            $this->inboxRule = $this->queryInboxRules($task);
            
            //The Foreach has only inbox rules ACTIVE=true and user_id = $task->user_id
            foreach ($this->inboxRule as $rule) {
                if ($rule->getAttribute('saved_search_id') !== null) {
                    return $this->loadSavedSearch($rule, $task);
                }

                if (
                    $rule->getAttribute('process_request_token_id') !== null &&
                    $task->process_id == $rule->task->process_id &&
                    $task->element_id == $rule->task->element_id
                ) {
                    return true;
                }
            }
        } else {
            return false;
        }
    }

    public function loadSavedSearch($rule, $task) {
        return $rule->savedSearch->query
                        ->where('process_request_tokens.user_id', $task->user_id)
                        ->where('process_request_tokens.id', $task->id)
                        ->exists();
    }

    public function queryInboxRules($task)
    {
        $this->inboxRule = InboxRule::with(['savedSearch', 'task'])
        ->where('inbox_rules.active', true);
        if (
            request()->has('saved_searches.user_id') &&
            request()->get('saved_searches.user_id') !== null
        ) {
            $this->inboxRule->where('saved_searches.user_id', $task->user_id);
        }

        if (
            request()->has('process_request_tokens.user_id') &&
            request()->get('process_request_tokens.user_id') !== null
        ) {
            $this->inboxRule->where('process_request_tokens.user_id', $task->user_id);
        }
        return $this->inboxRule->get();
    }
}
