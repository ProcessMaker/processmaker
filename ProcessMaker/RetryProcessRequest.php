<?php

namespace ProcessMaker;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Jobs\RunServiceTask;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestLock;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Bpmn\Models\ScriptTask;
use ProcessMaker\Nayra\Bpmn\Models\ServiceTask;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
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

    public function isChildRequest(): bool
    {
        return $this->processRequest->parentRequest()->exists();
    }

    public function hasRetriableTasks(): bool
    {
        return $this->retriableTasksQuery()->exists();
    }

    public function getRetriableTasks(): Collection
    {
        return $this->retriableTasksQuery()->get();
    }

    public function hasNonRetriableTasks(): bool
    {
        $currentTaskTypes = static::$taskTypes;

        $this->determineTaskTypes(true);

        $tasksQuery = $this->retriableTasksQuery();

        $tasksQuery = $tasksQuery->whereNotIn('element_type', $currentTaskTypes);

        $hasNonRetriableTasks = $tasksQuery->exists();

        $this->determineTaskTypes();

        return $hasNonRetriableTasks;
    }

    public function retry(): void
    {
        if (!$this->hasRetriableTasks()) {
            return;
        }

        $this->unlockProcessRequest();

        $this->getRetriableTasks()->each(function (ProcessRequestToken $token) {
            // Load the token instance
            $token = $token->loadTokenInstance();

            // Get the task to retry from the BPMN definitions
            $task = $this->bpmnDefinitions->getEvent($element = $token->element_id);

            if (!$task) {
                return;
            }

            $this->clearTokenErrors($token);

            if ($task instanceof ScriptTask) {
                WorkflowManager::runScripTask($task, $token);
            } elseif ($task instanceof ServiceTask) {
                WorkflowManager::runServiceTask($task, $token);
            }

            static::$output[] = $this->formatOutput($task, $element, $token);
        });

        $this->createRequestComment();
    }

    /**
     * Clear previous ProcessRequestToken errors off of its parent ProcessRequest(s)
     *
     * @param  ProcessRequestToken  $token
     *
     * @return void
     */
    public function clearTokenErrors(ProcessRequestToken $token): void
    {
        $errors = collect($token->processRequest->errors ?? []);

        if ($errors->isEmpty()) {
            return;
        }

        $errors = $errors->keyBy('element_id');

        if ($errors->has($element = $token->element_id)) {
            $errors->forget($element);
        }

        $token->processRequest->errors = $errors->all();

        if (!$token->processRequest->isNonPersistent()) {
            $token->processRequest->save();
        }
    }

    public function formatOutput($task, $element, $token): string
    {
        return 'Retrying ' . class_basename($task) . " ({$element}) for Request::{$token->processRequest->id}";
    }

    public function initializingUser(): User|bool
    {
        return Auth::check() ? Auth::user() : false;
    }

    public function createRequestComment(): void
    {
        $comment = (new Comment)->fill([
            'type' => 'LOG',
            'commentable_type' => ProcessRequest::class,
            'commentable_id' => $this->processRequest->id,
            'subject' => __('Request Retried'),
            'case_number' => isset($this->processRequest->case_number) ? $this->processRequest->case_number : null,
        ]);

        if ($user = $this->initializingUser()) {
            $comment->setAttribute('user_id', $user->id);
            $comment->setAttribute('body', $user->fullname . ' ' . __('retried the request from an error state'));
        } else {
            $comment->setAttribute('body', __('Request was retried from an error state'));
        }

        $comment->save();
    }

    private function determineTaskTypes(bool $all = false): void
    {
        if ($all || app()->runningInConsole()) {
            static::$taskTypes = ['scriptTask', 'serviceTask', 'task'];
        } else {
            static::$taskTypes = ['scriptTask'];
        }
    }

    public function retriableTasksQuery(): HasMany
    {
        $tokensQuery = $this->processRequest->tokens();

        $tokensQuery->whereIn('status', ['FAILING', 'ACTIVE', 'ERROR']);

        $tokensQuery->whereIn('element_type', static::$taskTypes);

        return $tokensQuery;
    }

    public function unlockProcessRequest(): void
    {
        ProcessRequestLock::whereIn('process_request_id', $this->getRequestsFromTokens()->all())
                          ->delete();
    }

    public function reactivateRequest(): bool
    {
        $reactivated = true;

        $this->getRequestsFromTokens()->each(
            function ($request) use ($reactivated) {
                $success = ProcessRequest::findOrFail($request)
                                         ->fill(['status' => 'ACTIVE'])
                                         ->save();

                if (!$success) {
                    $reactivated = false;
                }
            });

        return $reactivated;
    }

    public function getRequestsFromTokens()
    {
        return $this->getRetriableTasks()->pluck('process_request_id');
    }

    public function getBpmnDefinitions(): BpmnDocument
    {
        return $this->processRequest->process->getDefinitions();
    }
}
