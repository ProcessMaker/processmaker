<?php

namespace ProcessMaker\InboxRules;

use Facades\ProcessMaker\InboxRules\MatchingTasks;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\TaskDraft;
use ProcessMaker\Models\User;
use ProcessMaker\SanitizeHelper;

class ApplyAction
{
    public function applyActionOnTask(ProcessRequestToken $task, array $inboxRules)
    {
        foreach ($inboxRules as $inputRule) {
            //Mark as priority
            if ($inputRule->mark_as_priority === true) {
                $this->markAsPriority($task);
            }

            //Reassign to user id
            if ($inputRule->reassign_to_user_id !== null) {
                $this->reassignToUserID($task, $inputRule);
            }

            //If $savedSearchId is null For Task Rules only
            if ($inputRule->task) {
                //Fill and save as draft
                if ($inputRule->make_draft === true) {
                    $this->saveAsDraft($task, $inputRule);
                }
                //Submit the form
                if ($inputRule->submit_data === true) {
                    $this->submitForm($task, $inputRule);
                }
            }
        }
    }

    public function submitForm($task, $inputRule)
    {
        if ($task->status === 'CLOSED') {
            return abort(422, __('Task already closed'));
        }
        $data = $inputRule->data;
        // Call the manager to trigger the start event
        $process = $task->process;
        $instance = $task->processRequest;
        WorkflowManager::completeTask($process, $instance, $task, $data);
    }

    public function reassignToUserID($task, $inputRule)
    {
        $inboxRuleUser = User::findOrFail($inputRule->user_id);
        $task->reassign($inputRule->reassign_to_user_id, $inboxRuleUser);
    }

    public function markAsPriority($task)
    {
        $task->update(['is_priority' => true]);
    }

    public function saveAsDraft($task, $inputRule)
    {
        //Only not null or not empty data is going to be stored
        TaskDraft::updateOrCreate(
            ['task_id' => $task->id],
            ['data' => $inputRule->data ?? null]
        );
    }
}
