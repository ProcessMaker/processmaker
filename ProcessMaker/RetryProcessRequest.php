<?php

namespace ProcessMaker;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Jobs\RunServiceTask;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestLock;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Bpmn\Models\ScriptTask;
use ProcessMaker\Nayra\Bpmn\Models\ServiceTask;
use ProcessMaker\Repositories\BpmnDocument;

class RetryProcessRequest
{
    public static array $output = [];

    private static array $taskTypes = [];

    private ProcessRequest $processRequest;

    private BpmnDocument $bpmnDefinitions;

    public function __construct(ProcessRequest $processRequest)
    {
        $this->processRequest = $processRequest;

        $this->bpmnDefinitions = $this->getBpmnDefinitions();

        $this->determineTaskTypes();
    }

    public static function for(ProcessRequest $processRequest): self
    {
        return new self($processRequest);
    }

    public function hasRetriableTasks(): bool
    {
        return $this->retriableTasksQuery()->exists();
    }

    public function retry(): void
    {
        if (!$this->hasRetriableTasks()) {
            return;
        }

        $this->unlockProcessRequest();

        $this->getRetriableTasks()->each(function (ProcessRequestToken $token) {
            // Get the task to retry from the BPMN definitions
            $task = $this->bpmnDefinitions->getEvent($element = $token->element_id);

            // $job will remain false if we can't find the correct
            // job type to dispatch in order to retry the task
            $job = false;

            // Find the correct job type to retry the task
            switch ($task::class) {
                case ScriptTask::class:
                    $job = RunScriptTask::class;
                    break;

                case ServiceTask::class:
                    $job = RunServiceTask::class;
                    break;
            }

            if (!$job) {
                return;
            }

            $output = 'Retrying ' . class_basename($task) . " ({$element}) for Request::{$token->processRequest->id}";

            static::$output[] = $output;

            Log::info($output, [
                'user_initializing_retry' => $this->initializingUser(),
                'token' => $token,
            ]);

            $job::dispatch($token->processRequest->process, $token->processRequest, $token, []);
        });

        $this->createRequestComment();
    }

    protected function initializingUser(): User|bool
    {
        return Auth::check() ? Auth::user() : false;
    }

    protected function createRequestComment(): void
    {
        $comment = (new Comment)->fill([
            'type' => 'LOG',
            'commentable_type' => ProcessRequest::class,
            'commentable_id' => $this->processRequest->id,
            'subject' => __('Request Retried'),
        ]);

        if ($user = $this->initializingUser()) {
            $comment->setAttribute('user_id', $user->id);
            $comment->setAttribute('body', $user->fullname . ' ' . __('retried the request from an error state'));
        } else {
            $comment->setAttribute('body', __('Request was retried from an error state'));
        }

        $comment->save();
    }

    private function determineTaskTypes(): void
    {
        if (app()->runningInConsole()) {
            static::$taskTypes = ['scriptTask', 'serviceTask', 'task'];
        } else {
            static::$taskTypes = ['scriptTask'];
        }
    }

    protected function getRetriableTasks(): Collection
    {
        return $this->retriableTasksQuery()->get();
    }

    protected function retriableTasksQuery(): HasMany
    {
        $tokensQuery = $this->processRequest->tokens();

        $tokensQuery->whereIn('status', ['FAILING', 'ACTIVE', 'ERROR']);

        $tokensQuery->whereIn('element_type', static::$taskTypes);

        return $tokensQuery;
    }

    protected function unlockProcessRequest(): void
    {
        ProcessRequestLock::where('process_request_id', $this->processRequest->id)
                          ->delete();
    }

    protected function getBpmnDefinitions(): BpmnDocument
    {
        return $this->processRequest->process->getDefinitions();
    }
}
