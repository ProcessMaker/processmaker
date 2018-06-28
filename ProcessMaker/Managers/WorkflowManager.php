<?php

namespace ProcessMaker\Managers;

use Illuminate\Support\Facades\Log;
use ProcessMaker\Jobs\CallProcess;
use ProcessMaker\Jobs\CompleteActivity;
use ProcessMaker\Jobs\StartEvent;
use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\StartEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Model\Process as Definitions;

class WorkflowManager
{

    public function completeTask(Definitions $definitions, ExecutionInstanceInterface $instance, TokenInterface $token, array $data)
    {
        return CompleteActivity::dispatch($definitions, $instance, $token, $data);
    }

    /**
     * 
     * @param Definitions $definitions
     * @param StartEventInterface $event
     * @return type
     */
    public function triggerStartEvent(Definitions $definitions, StartEventInterface $event, array $data)
    {
        //@todo Validate user permissions
        //Log BPMN actions
        Log::info(sprintf('Schedule start "%s" at "%s"', $event->getId(), $definitions->title));
        //Schedule BPMN Action
        return StartEvent::dispatch($definitions, $event, $data);
    }

    public function triggerIntermediateEvent()
    {

    }

    public function callProcess(Definitions $definitions, ProcessInterface $process)
    {
        //Validate user permissions
        //Validate BPMN rules
        //Log BPMN actions
        //Schedule BPMN Action
        return CallProcess::dispatch($definitions, $process);
    }

    public function runScripTask($filename, $processId, $instanceId, $tokenId)
    {
    }
}
