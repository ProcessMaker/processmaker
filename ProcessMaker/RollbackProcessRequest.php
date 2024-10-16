<?php

namespace ProcessMaker;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Events\ActivityAssigned;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Repositories\BpmnDocument;

class RollbackProcessRequest
{
    /**
     * Element types that can be rolled-back to
     *
     * @var array
     */
    private $eligibleTypes = ['task', 'scriptTask', 'serviceTask'];

    /**
     * The current task (that is presumably failing or has some problem)\
     *
     * @var ProcessRequestToken
     */
    private $currentTask;

    /**
     * A previously completed task that we want to rollback to.
     *
     * @var ProcessRequestToken
     */
    private $rollbackToTask;

    /**
     * A copy of the rollbackToTask that will be the new active task.
     *
     * @var ProcessRequestToken
     */
    private $newTask;

    /**
     * BPMN Definitions for the process
     *
     * @var ProcessMaker\Repositories\BpmnDocument
     */
    private $processDefinitions;

    /**
     * Return the last task if its status was failing.
     * We may need to update this logic later.
     *
     * @param ProcessRequest $processRequest
     *
     * @return ProcessRequestToken|null
     */
    public function getErrorTask(ProcessRequest $processRequest) : ?ProcessRequestToken
    {
        $lastTask = $processRequest->tokens()->orderBy('id', 'desc')->first();

        if (!$lastTask) {
            return null;
        }

        if ($lastTask->status === 'FAILING') {
            return $lastTask;
        }

        // Allow for gateway tasks
        if (
            $lastTask->element_type === 'gateway' &&
            $lastTask->status === 'CLOSED' &&
            $processRequest->status === 'ERROR'
        ) {
            return $lastTask;
        }

        return null;
    }

    /**
     * Find an element in the request that can be rolled-back to.
     * Return null if none are eligible.
     *
     * @return ProcessRequestToken|null
     */
    public function eligibleRollbackTask(ProcessRequestToken $currentTask) : ?ProcessRequestToken
    {
        $processRequest = $currentTask->processRequest;

        return $processRequest->tokens()
            ->where('status', 'CLOSED')
            ->where('id', '<', $currentTask->id)
            ->where('element_id', '!=', $currentTask->element_id)
            ->whereIn('element_type', $this->eligibleTypes)
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Rollback a token to the previous token and reset the request status.
     *
     * @param ProcessRequestToken $task
     * @return ProcessRequestToken
     */
    public function rollback(
        ProcessRequestToken $currentTask,
        BpmnDocument $processDefinitions
        ) : ProcessRequestToken {
        $this->currentTask = $currentTask;
        $this->processDefinitions = $processDefinitions;
        $this->rollbackToTask = $this->eligibleRollbackTask($currentTask);
        if (!$this->rollbackToTask) {
            throw new \Exception('No eligible rollback task found');
        }

        switch ($this->rollbackToTask->element_type) {
            case 'scriptTask':
                $this->rollbackToScriptTask();
                break;
            case 'serviceTask':
                $this->rollbackToServiceTask();
                break;
            case 'task':
                $this->rollbackToTask();
                break;
        }

        $processRequest = $this->newTask->processRequest;
        $processRequest->status = 'ACTIVE';
        $process = $processRequest->process;
        $processRequest->process_version_id = $process->getLatestVersion(
            $processRequest->processVersion->alternative
        )->id;
        $processRequest->saveOrFail();

        $currentTask->status = 'CLOSED';
        $currentTask->saveOrFail();

        return $this->newTask;
    }

    private function rollbackToTask()
    {
        $this->copyTask();

        if ($this->newTask->user_id) {
            $this->newTask->sendActivityActivatedNotifications();
        }
        if ($this->newTask->element_type === 'task') {
            event(new ActivityAssigned($this->newTask));
        }

        $this->addComment();
    }

    private function rollbackToScriptTask()
    {
        $this->copyTask();

        $bpmnTask = $this->processDefinitions->getEvent($this->newTask->element_id);

        WorkflowManager::runScripTask($bpmnTask, $this->newTask);

        $this->addComment();
    }

    private function rollbackToServiceTask()
    {
        $this->copyTask();

        $bpmnTask = $this->processDefinitions->getEvent($this->newTask->element_id);

        WorkflowManager::runServiceTask($bpmnTask, $this->newTask);

        $this->addComment();
    }

    private function copyTask()
    {
        $newTask = $this->rollbackToTask->replicate();
        $newTask->uuid = null;
        $newTask->status = 'ACTIVE';
        $newTask->saveOrFail();
        $this->newTask = $newTask;
    }

    /**
     * Add a comment that the task was rolled back.
     *
     * @return void
     */
    private function addComment() : void
    {
        $user = Auth::user();
        $userName = $user ? $user->fullname : __('The System');
        Comment::create([
            'type' => 'LOG',
            'user_id' => $user ? $user->id : null,
            'commentable_type' => ProcessRequest::class,
            'commentable_id' => $this->currentTask->process_request_id,
            'subject' => 'Rollback',
            'body' => __(':user rolled back :failed_task_name to :new_task_name', [
                'user' => $userName,
                'failed_task_name' => $this->currentTask->element_name,
                'new_task_name' => $this->newTask->element_name,
            ]),
            'case_number' => isset($this->currentTask->case_number) ? $this->currentTask->case_number : null,
        ]);
    }
}
