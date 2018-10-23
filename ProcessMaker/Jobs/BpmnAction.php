<?php
namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use ProcessMaker\Models\Process as Definitions;

abstract class BpmnAction implements ShouldQueue
{

    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * Return a list of tags to be used by horizon for tagging this job
     */
    public function tags() 
    {
        return ['bpmn'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //Load the process definition
        $processModel = Definitions::withUuid($this->definitionsId)->first();
        $definitions = $processModel->getDefinitions();

        //Load the instances of the process and its collaborators
        $instance = isset($this->instanceId) ? $definitions->getEngine()->loadExecutionInstance($this->instanceId) : null;
        if ($instance && $instance->collaboration) {
            foreach ($instance->collaboration->requests as $request) {
                if ($request->uuid !== $instance->uuid) {
                    $definitions->getEngine()->loadExecutionInstance($request->uuid_text);
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
        $definitions->getEngine()->runToNextState();

        return $response;
    }
}
