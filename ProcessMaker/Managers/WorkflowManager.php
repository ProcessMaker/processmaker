<?php

namespace ProcessMaker\Managers;

use Illuminate\Support\Facades\Log;
use ProcessMaker\Jobs\CallProcess;
use ProcessMaker\Jobs\CompleteActivity;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Jobs\StartEvent;
use ProcessMaker\Model\Delegation;
use ProcessMaker\Model\Process as Definitions;
use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\StartEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;

class WorkflowManager
{

    public function completeTask(Definitions $definitions, ExecutionInstanceInterface $instance, TokenInterface $token, array $data)
    {
        return CompleteActivity::dispatchNow($definitions, $instance, $token, $data);
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
        return StartEvent::dispatchNow($definitions, $event, $data);
    }

    public function triggerIntermediateEvent()
    {

    }

    public function callProcess(Definitions $definitions, ProcessInterface $process, array $data)
    {
        //Validate user permissions
        //Validate BPMN rules
        //Log BPMN actions
        //Schedule BPMN Action
        return CallProcess::dispatchNow($definitions, $process, $data);
    }

    public function runScripTask(ScriptTaskInterface $scriptTask, Delegation $token)
    {
        $instance = $token->application;
        $process = $instance->process;
        //Run the script task with a delay to allow the request response to conclude, before the script runs
        return RunScriptTask::dispatch($process, $instance, $token, [])->delay(1);
    }
}
