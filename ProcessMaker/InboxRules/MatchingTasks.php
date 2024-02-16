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
            
            $this->inboxRule = InboxRule::with(['savedSearch', 'task'])
                ->select('inbox_rules.*', 'saved_searches.user_id as saved_search_user_id', 'process_request_tokens.user_id as process_request_token_user_id')
                ->leftJoin('saved_searches', 'inbox_rules.saved_search_id', '=', 'saved_searches.id')
                ->leftJoin('process_request_tokens', 'inbox_rules.process_request_token_id', '=', 'process_request_tokens.id')
                ->where('inbox_rules.active', true);


            if (request()->has('saved_searches.user_id') && request()->get('saved_searches.user_id') !== null) {
                $this->inboxRule->where('saved_searches.user_id', $task->user_id);
            }


            if (request()->has('process_request_tokens.user_id') && request()->get('process_request_tokens.user_id') !== null) {
                $this->inboxRule->where('process_request_tokens.user_id', $task->user_id);
            }

            $this->inboxRule = $this->inboxRule->get();

            //The Foreach has only inbox rules ACTIVE=true and user_id = $task->user_id
            foreach ($this->inboxRule as $rule) {
                if ($rule->getAttribute('saved_search_id') !== null) {

                    $res = $rule->savedSearch->query
                        ->where('process_request_tokens.user_id', $rule->saved_search_user_id)
                        ->where('process_request_tokens.id', $task->id)
                        ->exists();

                    if (!$res) {
                        return $res;
                    }
                }

                if ($rule->getAttribute('process_request_token_id') !== null) {
                    if (isset($rule->task)) {
                        if ($task->process_id == $rule->task->process_id && $task->element_id == $rule->task->element_id) {
                            return true;
                        }
                    }
                }
            }
            return false;
        } else {
            return false;
        }
    }
}
