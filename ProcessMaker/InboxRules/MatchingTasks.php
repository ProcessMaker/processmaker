<?php

namespace ProcessMaker\InboxRules;

use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\InboxRule;
use ProcessMaker\Package\SavedSearch\Models\SavedSearch;

class MatchingTasks
{
    protected $inboxRule;

    public function __construct(InboxRule $inboxRule)
    {
        $this->inboxRule = $inboxRule;
    }

    public function check(ProcessRequestToken $task) 
    {
        if ($task->isNotEmpty() && $task->has('user_id')) {

            $this->inboxRule = InboxRule::where('user_id', $task->user_id)->where('active', true)->get();

            $savedSearch = new SavedSearch();

            foreach ($this->inboxRule as $rule) {
                if (property_exists($rule, 'saved_search_id') && $rule->saved_search_id !== null) {   
                    //then is a save search rule
                    $result = $savedSearch->query()
                    ->where('process_request_token.user_id', $this->inboxRule->user_id)
                    ->exists();

                    if ($result) {
                        return true;
                        $processId = $task->process_id;
                        $elementId = $task->element_id;
                    }

                }
                if (property_exists($rule, 'process_request_token_id') && $rule->process_request_token_id !== null) { 
                    $result = $savedSearch->query()
                    ->where('process_request_token.id', $task->id)
                    ->exists();
                }
   
            }
        } else {
            return;
        }
    }
}
