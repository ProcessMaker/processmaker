<?php
namespace ProcessMaker\Jobs;

use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use Illuminate\Support\Facades\App;
use ProcessMaker\Models\Process as Definitions;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class TokenAction extends BpmnAction
{

    public $definitionsId;
    public $instanceId;
    public $tokenId;
    public $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Definitions $definitions, ExecutionInstanceInterface $instance, TokenInterface $token, array $data)
    {
        $this->definitionsId = $definitions->uuid_text;
        $this->instanceId = $instance->uuid_text;
        $this->tokenId = $token->uuid_text;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //Load the process definition
        $definitions = Definitions::withUuid($this->definitionsId)->first();
        $workflow = $definitions->getDefinitions();

        //Load the instances of the process and its collaborators
        $instance = $workflow->getEngine()->loadExecutionInstance($this->instanceId);
        if ($instance->collaboration) {
            foreach ($instance->collaboration->requests as $request) {
                if ($request->uuid !== $instance->uuid) {
                    $workflow->getEngine()->loadExecutionInstance($request->uuid_text);
                }
            }
        }
        if (!$instance) {
            return;
        }

        $token = null;
        $element = null;
        foreach($instance->getTokens() as $token) {
            if ($token->getId() === $this->tokenId) {
                $element = $workflow->getElementInstanceById($token->getProperty('element_ref'));
                break;
            } else {
                $token = null;
            }
        }
        $activity = $element;
        $data = $this->data;

        //Do the action
        App::call([$this, 'action'], compact('workflow', 'instance', 'token', 'element', 'activity', 'data'));

        //Run engine to the next state
        $workflow->getEngine()->runToNextState();
    }
}
