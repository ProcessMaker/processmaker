<?php

namespace ProcessMaker\Managers;

use Illuminate\Support\Facades\Log;
use ProcessMaker\Jobs\CallProcess;
use ProcessMaker\Jobs\CompleteActivity;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Jobs\RunServiceTask;
use ProcessMaker\Jobs\StartEvent;
use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Models\ProcessRequestToken as Token;
use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ServiceTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\StartEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;

class WorkflowManager
{

    /**
     * Complete a task.
     *
     * @param Definitions $definitions
     * @param ExecutionInstanceInterface $instance
     * @param TokenInterface $token
     * @param array $data
     */
    public function completeTask(Definitions $definitions, ExecutionInstanceInterface $instance, TokenInterface $token, array $data)
    {
        CompleteActivity::dispatchNow($definitions, $instance, $token, $data);
    }

    /**
     * Trigger an start event and return the instance.
     *
     * @param Definitions $definitions
     * @param StartEventInterface $event
     *
     * @return \ProcessMaker\Models\ProcessRequest
     */
    public function triggerStartEvent(Definitions $definitions, StartEventInterface $event, array $data)
    {
        //@todo Validate user permissions
        //Schedule BPMN Action
        return StartEvent::dispatchNow($definitions, $event, $data);
    }

    /**
     * Start a process instance.
     *
     * @param Definitions $definitions
     * @param ProcessInterface $process
     * @param array $data
     *
     * @return \ProcessMaker\Models\ProcessRequest
     */
    public function callProcess(Definitions $definitions, ProcessInterface $process, array $data)
    {
        //Validate user permissions
        //Validate BPMN rules
        //Log BPMN actions
        //Schedule BPMN Action
        return CallProcess::dispatchNow($definitions, $process, $data);
    }

    /**
     * Run a script task.
     *
     * @param ScriptTaskInterface $scriptTask
     * @param Token $token
     */
    public function runScripTask(ScriptTaskInterface $scriptTask, Token $token)
    {
        Log::info('Dispatch a script task: ' . $scriptTask->getId());
        $instance = $token->processRequest;
        $process = $instance->process;
        RunScriptTask::dispatch($process, $instance, $token, [])->delay(1);
    }

    /**
     * Run a service task.
     *
     * @param ServiceTaskInterface $serviceTask
     * @param Token $token
     */
    public function runServiceTask(ServiceTaskInterface $serviceTask, Token $token)
    {
        Log::info('Dispatch a service task: ' . $serviceTask->getId());
        $instance = $token->processRequest;
        $process = $instance->process;
        RunServiceTask::dispatch($process, $instance, $token, []);
    }
}
