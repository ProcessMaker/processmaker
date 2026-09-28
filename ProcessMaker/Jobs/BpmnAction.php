<?php

namespace ProcessMaker\Jobs;

use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use ProcessMaker\BpmnEngine;
use ProcessMaker\Exception\HttpABTestingException;
use ProcessMaker\Listeners\HandleRedirectListener;
use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestLock;
use ProcessMaker\Models\ProcessRequestToken;
use Throwable;

abstract class BpmnAction implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * @var BpmnEngine
     */
    protected $engine;

    /**
     * @var ProcessRequest
     */
    protected $instance;

    protected $instanceId;

    protected $tokenId = null;

    protected $disableGlobalEvents = false;

    protected $data;

    protected $processId;

    /**
     * Context loaded at the beginning of the job. It can be reused after an
     * external action when the persisted execution state has not changed.
     *
     * @var array|null
     */
    private $loadedContext;

    /** @var int|null */
    private $loadedExecutionRevision;

    /**
     * @var ProcessRequestLock
     */
    private $lock;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $response = null;
        $currentAction = $this;
        try {
            $this->loadedContext = $this->loadContext();
            $this->loadedExecutionRevision = $this->loadedContext['instance']?->execution_revision;
            extract($this->loadedContext);
            $this->engine = $engine;
            $this->instance = $instance;

            // Do the action
            $response = App::call([$this, 'action'], compact('definitions', 'instance', 'token', 'process', 'element', 'data', 'processModel'));

            // Run engine to the next state
            $this->engine->runToNextState();

            while ($inlineJob = $currentAction->engine->pullInlineJob()) {
                $context = $currentAction->loadedContext;
                $context['token'] = $inlineJob['context']['token'];
                $context['instance'] = $inlineJob['context']['instance'];
                $context['element'] = $inlineJob['context']['element'];
                $currentAction->loadedContext = $context;
                $currentAction->loadedExecutionRevision = $context['instance']->execution_revision;
                $currentAction->transferInternalContext($inlineJob['job']);
                $currentAction = $inlineJob['job'];

                $response = App::call([$currentAction, 'action'], $context);
                $currentAction->engine->runToNextState();
            }

            // call to redirect after all events are completed
            // (e.g. completed, assigned, process completed, etc)
            // excluding system process (non_persistent_process)
            if ($this->processId !== 'non_persistent_process') {
                HandleRedirectListener::sendRedirectToEvent();
            }
        } catch (HttpABTestingException $exception) {
            Log::error($exception->getMessage());
            throw $exception;
        } catch (Throwable $exception) {
            Log::error($exception->getMessage());
            // Change the Request to error status
            $request = !$currentAction->instance && $currentAction instanceof StartEvent ? $response : $currentAction->instance;
            if ($request) {
                $request->logError($exception, $context['element'] ?? $element ?? null);
            }
        } finally {
            $currentAction->unlock();
        }

        return $response;
    }

    public function transferInternalContext(BpmnAction $action): void
    {
        $action->engine = $this->engine;
        $action->instance = $this->instance;
        $action->loadedContext = $this->loadedContext;
        $action->loadedExecutionRevision = $this->loadedExecutionRevision;
        $action->lock = $this->lock;
        $action->disableGlobalEvents = $this->disableGlobalEvents;
        $this->lock = null;
    }

    /**
     * Load the context for the action
     *
     * @return array
     */
    private function loadContext(?ProcessRequest $lockedInstance = null)
    {
        // Load the process definition
        if (isset($this->instanceId)) {
            $instance = $lockedInstance ?: $this->lockInstance($this->instanceId);
            $processModel = $instance->process;
            $definitions = ($instance->processVersion ?? $instance->process)->getDefinitions(true);
            $engine = app(BpmnEngine::class, ['definitions' => $definitions, 'globalEvents' => !$this->disableGlobalEvents]);
            $instance = $engine->loadProcessRequest($instance);
        } else {
            $processModel = Definitions::find($this->definitionsId);
            $definitions = $processModel->getPublishedVersion($this->data ?: [])->getDefinitions();
            $engine = app(BpmnEngine::class, ['definitions' => $definitions, 'globalEvents' => !$this->disableGlobalEvents]);
            $instance = null;
        }

        $engine->setInlineTaskExecutionEnabled($this->allowsInlineTaskExecution());

        // Load the instances of the process and its collaborators
        if ($instance && $instance->collaboration) {
            $activeRequests = $instance->collaboration->requests()->where('status', 'ACTIVE')->get();
            foreach ($activeRequests as $request) {
                if ($request->getKey() !== $instance->getKey()) {
                    $engine->loadProcessRequest($request);
                }
            }
        }

        // Get the BPMN process instance
        $process = null;
        if (isset($this->processId)) {
            $process = $definitions->getProcess($this->processId);
        }

        // Load token and element
        $token = null;
        $element = null;
        if ($instance && isset($this->tokenId)) {
            foreach ($instance->getTokens() as $token) {
                if ($token->getId() === $this->tokenId) {
                    $element = $definitions->getElementInstanceById($token->getProperty('element_ref'));
                    break;
                } else {
                    $token = null;
                }
            }
        } elseif (isset($this->elementId)) {
            $element = $definitions->getElementInstanceById($this->elementId);
        }

        // Load data
        $data = isset($this->data) ? $this->data : null;

        return compact('definitions', 'instance', 'token', 'process', 'element', 'data', 'processModel', 'engine');
    }

    protected function allowsInlineTaskExecution(): bool
    {
        return $this instanceof RunScriptTask || $this instanceof RunServiceTask;
    }

    /**
     * This method execute a callback with the context updated
     *
     * @return array
     */
    public function withUpdatedContext(callable $callable)
    {
        $lockedInstance = $this->lockInstance($this->instanceId, true);
        $contextReused = $this->canReuseLoadedContext($lockedInstance);
        if ($contextReused) {
            $context = $this->loadedContext;
        } else {
            $context = $this->loadContext(ProcessRequest::findOrFail($this->instanceId));
        }

        $this->loadedContext = $context;
        $this->loadedExecutionRevision = $context['instance']?->execution_revision;

        return App::call($callable, $context);
    }

    /**
     * Determine whether the in-memory engine still represents the persisted
     * request. This optimization is intentionally limited to linear states.
     * true: can reuse the loaded context
     * false: cannot reuse the loaded context
     * null: cannot determine if the context can be reused
     */
    private function canReuseLoadedContext(ProcessRequest $lockedInstance): bool
    {
        $activeTokenIds = [];
        if ((int) $lockedInstance->execution_revision === (int) $this->loadedExecutionRevision) {
            $activeTokenIds = ProcessRequestToken::query()
                ->where('process_request_id', $this->instanceId)
                ->whereNotIn('status', BpmnEngine::INACTIVE_TOKEN_STATUSES)
                ->limit(2)
                ->pluck('id')
                ->all();
        }

        $fallbackReason = app(BpmnContextReuseGuard::class)->fallbackReason(
            $this->loadedContext['instance'] ?? null,
            $lockedInstance,
            $this->loadedExecutionRevision,
            $activeTokenIds
        );

        return $fallbackReason === null;
    }

    /**
     * Lock the instance and its collaborators
     *
     * @param int $instanceId
     *
     * @return ProcessRequest
     */
    private function lockInstance($instanceId, bool $lightweight = false)
    {
        try {
            // First attempt to find the instance with retry logic for race conditions
            $instance = $this->findInstanceWithRetry($instanceId, $lightweight);

            if (config('queue.default') === 'sync') {
                return $instance;
            }
            if ($instance->collaboration) {
                $ids = $instance->collaboration->requests()->where('status', 'ACTIVE')->pluck('id')->toArray();
            } else {
                $ids = [$instance->id];
            }
            $lock = $this->requestLock($ids);
            // If the processes are going to have thousands of parallel instances,
            // the lock will be released after a while.
            $timeout = config('app.bpmn_actions_max_lock_timeout', 60000) ?: 60000;
            $interval = config('app.bpmn_actions_lock_check_interval', 1000) ?: 1000;
            $maxRetries = ceil($timeout / $interval);
            for ($tries = 0; $tries < $maxRetries; $tries++) {
                $currentLock = $this->currentLock($ids);
                if (!$currentLock) {
                    if (ProcessRequest::whereKey($instanceId)->exists()) {
                        $lock = $this->requestLock($ids);
                    } else {
                        throw new Exception('Unable to lock instance #' . $this->instanceId . ': Request does not exists');
                    }
                } elseif ($lock->id == $currentLock->id) {
                    $instance = $this->findInstance($instanceId, $lightweight);
                    $this->activateLock($lock);

                    return $instance;
                }
                // average of lock time is 1 second
                $this->mSleep($interval);
            }
        } catch (Throwable $exception) {
            throw new Exception('Unable to lock instance #' . $this->instanceId . ': ' . $exception->getMessage());
        }
        throw new Exception('Unable to lock instance #' . $this->instanceId . ": Timeout {$timeout}[ms]");
    }

    /**
     * Find ProcessRequest with retry logic to handle race conditions
     *
     * @param int $instanceId
     * @return ProcessRequest
     * @throws Exception
     */
    private function findInstanceWithRetry($instanceId, bool $lightweight = false)
    {
        $maxRetries = config('app.bpmn_actions_find_retries', 5);
        $retryDelay = config('app.bpmn_actions_find_retry_delay', 50); // milliseconds

        // Always attempt at least once, regardless of maxRetries value
        $totalAttempts = max(1, $maxRetries);

        for ($attempt = 0; $attempt < $totalAttempts; $attempt++) {
            try {
                $instance = $this->findInstance($instanceId, $lightweight);

                return $instance;
            } catch (ModelNotFoundException $e) {
                if ($attempt === $totalAttempts - 1) {
                    // Last attempt failed, re-throw the exception
                    throw $e;
                }

                // Wait before retrying (exponential backoff)
                $delay = $retryDelay * pow(2, $attempt);
                $this->mSleep($delay);

                Log::warning("ProcessRequest #{$instanceId} not found, retrying in {$delay}ms (attempt " . ($attempt + 1) . "/{$totalAttempts})");
            }
        }

        throw new ModelNotFoundException("ProcessRequest #{$instanceId} not found after {$totalAttempts} attempts");
    }

    /**
     * Load ProcessRequest for BPMN actions. In lightweight mode, only lock and
     * revision metadata are loaded when validating the fast path.
     */
    private function findInstance($instanceId, bool $lightweight): ProcessRequest
    {
        $query = ProcessRequest::query();
        if ($lightweight) {
            $query->select([
                'id',
                'process_collaboration_id',
                'execution_revision',
            ]);
        } else {
            $query->with([
                'process',
                'processVersion',
                'collaboration',
            ]);
        }

        return $query->findOrFail($instanceId);
    }

    /**
     * Request a lock for the instance
     * @param array $ids
     * @return ProcessRequestLock
     */
    protected function requestLock($ids)
    {
        return ProcessRequestLock::create([
            'request_id' => $this->instanceId,
            'token_id' => $this->tokenId,
            'request_ids' => $ids,
        ]);
    }

    /**
     * Get the current lock
     * @param array $ids
     * @return ProcessRequestLock|null
     */
    protected function currentLock($ids)
    {
        $query = ProcessRequestLock::whereNotDue()
            ->orderBy('id', 'asc')
            ->limit(1);
        $query->where(function ($query) use ($ids) {
            foreach ($ids as $id) {
                $query->orWhereJsonContains('request_ids', $id);
            }
        });

        return $query->first();
    }

    /**
     * Activate the lock
     * @param ProcessRequestLock $lock
     * @return void
     */
    protected function activateLock(ProcessRequestLock $lock)
    {
        $lock->activate();
        $this->lock = $lock;
        // Remove due locks
        ProcessRequestLock::where('due_at', '<', Carbon::now())->delete();
    }

    /**
     * Unlock the instance and its collaborators
     */
    protected function unlock()
    {
        if (isset($this->lock)) {
            $this->lock->delete();
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags()
    {
        $tags = ['bpmn'];
        if (isset($this->definitionsId)) {
            $tags[] = 'processId:' . $this->definitionsId;
        }
        if (isset($this->instanceId)) {
            $tags[] = 'instanceId:' . $this->instanceId;
        }
        if (isset($this->tokenId)) {
            $tags[] = 'tokenId:' . $this->tokenId;
        }
        if (isset($this->elementId)) {
            $tags[] = 'elementId:' . $this->elementId;
        }

        return $tags;
    }

    /**
     * Sleep in milliseconds
     *
     * @param int $milliseconds
     */
    private function mSleep($milliseconds)
    {
        $seconds = floor($milliseconds / 1000);
        $microseconds = ($milliseconds % 1000) * 1000;
        sleep($seconds);
        usleep($microseconds);
    }

    public function __destruct()
    {
        $this->instance = null;
        $this->engine = null;
        $this->lock = null;
        $this->loadedContext = null;
        $this->loadedExecutionRevision = null;
        gc_collect_cycles();
    }
}
