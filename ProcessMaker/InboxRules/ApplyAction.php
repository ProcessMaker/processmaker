<?php

namespace ProcessMaker\InboxRules;

use Illuminate\Auth\Access\AuthorizationException;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\InboxRule;
use ProcessMaker\Models\InboxRuleLog;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\TaskDraft;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\ApplyActionNotification;

class ApplyAction
{
    public function applyActionOnTask(ProcessRequestToken $task, InboxRule $inboxRule)
    {
        \Log::info('Applying action on task ' . $task->id, ['inboxRule' => $inboxRule]);

        //Mark as priority
        if ($inboxRule->mark_as_priority) {
            $this->markAsPriority($task);
        }

        //Reassign to user id
        if ($inboxRule->reassign_to_user_id) {
            $this->reassignToUserID($task, $inboxRule);
        }

        //If $savedSearchId is null For Task Rules only
        if ($inboxRule->task) {
            //Fill and save as draft
            if ($inboxRule->make_draft) {
                $this->saveAsDraft($task, $inboxRule);
            }
            //Submit the form
            if ($inboxRule->submit_data) {
                $this->submitForm($task, $inboxRule);
            }
        }

        InboxRuleLog::create([
            'inbox_rule_id' => $inboxRule->id,
            'process_request_token_id' => $task->id,
            'inbox_rule_attributes' => $inboxRule->getAttributes(),
            'user_id' => $inboxRule->user_id,
        ]);
    }

    public function submitForm($task, $inboxRule)
    {
        if ($task->status === 'CLOSED') {
            return abort(422, __('Task already closed'));
        }
        $data = $inboxRule->data;
        // Call the manager to trigger the start event
        $process = $task->process;
        $instance = $task->processRequest;
        \Log::info('Completing task', [$process->id, $instance->id, $task->id, $data]);
        WorkflowManager::completeTask($process, $instance, $task, $data);
    }

    public function reassignToUserID($task, $inboxRule)
    {
        $inboxRuleUser = User::findOrFail($inboxRule->user_id);
        try {
            $task->reassign($inboxRule->reassign_to_user_id, $inboxRuleUser);
        } catch(AuthorizationException $e) {
            $message =
                'Task :task_id could not be reassigned because the task settings prevent it from being reassigned';
            $inboxRuleUser->notify(
                new ApplyActionNotification(__($message, ['task_id' => $task->id]))
            );
        }
    }

    public function markAsPriority($task)
    {
        $task->update(['is_priority' => true]);
    }

    public function saveAsDraft($task, $inboxRule)
    {
        //Only not null or not empty data is going to be stored
        TaskDraft::updateOrCreate(
            ['task_id' => $task->id],
            ['data' => $inboxRule->data ?? null]
        );
    }
}
