<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use ProcessMaker\Models\Process as Definitions;
use Throwable;

abstract class BpmnAction implements ShouldQueue
{

    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //Load the process definition
        $processModel = Definitions::find($this->definitionsId);
        $definitions = $processModel->getDefinitions();

        //Load the instances of the process and its collaborators
        $instance = isset($this->instanceId) ? $definitions->getEngine()->loadExecutionInstance($this->instanceId) : null;
        if ($instance && $instance->collaboration) {
            foreach ($instance->collaboration->requests as $request) {
                if ($request->getKey() !== $instance->getKey()) {
                    $definitions->getEngine()->loadExecutionInstance($request->getKey());
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

        //Do the action
        $response = App::call([$this, 'action'], compact('definitions', 'instance', 'token', 'process', 'element', 'data'));

        //Run engine to the next state
        try {
            $definitions->getEngine()->runToNextState();
        } catch (Throwable $exception) {
            // Change the Request to error status
            $request = !$instance && $this instanceof StartEvent ? $response : $instance;
            if ($request) {
                $request->logError($exception, $element);
            }
            throw $exception;
        }

        return $response;
    }
}
