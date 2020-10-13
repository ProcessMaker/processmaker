<?php

namespace ProcessMaker\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use ProcessMaker\BpmnEngine;
use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Models\ProcessRequest;
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

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        extract($this->loadContext());
        $this->engine = $engine;
        $this->instance = $instance;

        //Do the action
        $response = App::call([$this, 'action'], compact('definitions', 'instance', 'token', 'process', 'element', 'data', 'processModel'));

        //Run engine to the next state
        try {
            $this->engine->runToNextState();
        } catch (Throwable $exception) {
            // Change the Request to error status
            $request = !$this->instance && $this instanceof StartEvent ? $response : $this->instance;
            if ($request) {
                $request->logError($exception, $element);
            } else {
                throw $exception;
            }
        } finally {
            if (isset($this->instanceId)) {
                $this->unlockInstance($this->instanceId);
            };
        }

        return $response;
    }

    /**
     * Load the context for the action
     *
     * @return array
     */
    private function loadContext()
    {
        //Load the process definition
        if (isset($this->instanceId)) {
            $instance = $this->lockInstance($this->instanceId);
            $processModel = $instance->process;
            $definitions = ($instance->processVersion ?? $instance->process)->getDefinitions(true);
            $engine = app(BpmnEngine::class, ['definitions' => $definitions]);
            $instance = $engine->loadProcessRequest($instance);
        } else {
            $processModel = Definitions::find($this->definitionsId);
            $definitions = $processModel->getDefinitions();
            $engine = app(BpmnEngine::class, ['definitions' => $definitions]);
            $instance = null;
        }

        //Load the instances of the process and its collaborators
        if ($instance && $instance->collaboration) {
            foreach ($instance->collaboration->requests as $request) {
                if ($request->getKey() !== $instance->getKey()) {
                    $engine->loadProcessRequest($request);
                }
            }
        }

        //Get the BPMN process instance
        $process = null;
        if (isset($this->processId)) {
            $process = $definitions->getProcess($this->processId);
        }

        //Load token and element
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

        //Load data
        $data = isset($this->data) ? $this->data : null;

        return compact('definitions', 'instance', 'token', 'process', 'element', 'data', 'processModel', 'engine');
    }

    /**
     * This method execute a callback with the context updated
     *
     * @return array
     */
    public function withUpdatedContext(callable $callable)
    {
        $context = $this->loadContext();
        return App::call($callable, $context);
    }

    /**
     * Lock the instance and its collaborators
     *
     * @param int $instanceId
     *
     * @return ProcessRequest
     */
    protected function lockInstance($instanceId)
    {
        $instance = ProcessRequest::findOrFail($instanceId);
        if (config('queue.default') === 'sync') {
            return $instance;
        }
        $lock = $instance->lock($this->tokenId ?? null);
        do {
            $ready = $instance->hasLock($lock);
            if ($ready) {
                $instance = ProcessRequest::findOrFail($instanceId);
                $lock->save();
            } else {
                usleep(500);
            }
        } while (!$ready);
        return $instance;
    }

    /**
     * Lock the instance and its collaborators
     *
     * @param int $instanceId
     *
     * @return ProcessRequest
     */
    protected function unlockInstance($instanceId)
    {
        $instance = ProcessRequest::find($instanceId);
        $instance->unlock();
        return $instance;
    }
}
