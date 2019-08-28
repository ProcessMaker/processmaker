<?php

namespace ProcessMaker\Managers;

use Illuminate\Support\Facades\Log;
use ProcessMaker\Jobs\CallProcess;
use ProcessMaker\Jobs\CatchEvent;
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
use Illuminate\Support\Facades\Validator;
use ProcessMaker\Nayra\Contracts\Bpmn\BoundaryEventInterface;
use ProcessMaker\Jobs\BoundaryEvent;
use ProcessMaker\Nayra\Contracts\Bpmn\EntityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\FlowNodeInterface;

class WorkflowManager
{

    /**
     * Attached validation callbacks
     *
     * @var array
     */
    protected $validations = [];

    /**
     * Data Validator
     *
     * @var \Illuminate\Contracts\Validation\Validator $validator
     */
    protected $validator;

    /**
     * Complete a task.
     *
     * @param Definitions $definitions
     * @param ExecutionInstanceInterface $instance
     * @param TokenInterface $token
     * @param array $data
     *
     * @return void
     */
    public function completeTask(Definitions $definitions, ExecutionInstanceInterface $instance, TokenInterface $token, array $data)
    {
        //Validate data
        $element = $token->getDefinition(true);
        $this->validateData($data, $definitions, $element);
        CompleteActivity::dispatchNow($definitions, $instance, $token, $data);
    }

    /**
     * Complete a catch event
     *
     * @param Definitions $definitions
     * @param ExecutionInstanceInterface $instance
     * @param TokenInterface $token
     * @param array $data
     *
     * @return void
     */
    public function completeCatchEvent(Definitions $definitions, ExecutionInstanceInterface $instance, TokenInterface $token, array $data)
    {
        //Validate data
        $element = $token->getDefinition(true);
        $this->validateData($data, $definitions, $element);
        CatchEvent::dispatchNow($definitions, $instance, $token, $data);
    }

    /**
     * Trigger a boundary event
     *
     * @param Definitions $definitions
     * @param ExecutionInstanceInterface $instance
     * @param TokenInterface $token
     * @param BoundaryEventInterface $boundaryEvent
     * @param array $data
     *
     * @return void
     */
    public function triggerBoundaryEvent(
        Definitions $definitions,
        ExecutionInstanceInterface $instance,
        TokenInterface $token,
        BoundaryEventInterface $boundaryEvent,
        array $data
    ) {
        //Validate data
        $this->validateData($data, $definitions, $boundaryEvent);
        BoundaryEvent::dispatchNow($definitions, $instance, $token, $boundaryEvent, $data);
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
        //Validate data
        $this->validateData($data, $definitions, $event);
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
        //Validate data
        $this->validateData($data, $definitions, $process);
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

    /**
     * Attach validation event
     *
     * @param callable $callback
     * @return void
     */
    public function onDataValidation($callback)
    {
        $this->validations[] = $callback;
    }

    /**
     * Validate data
     *
     * @param array $data
     * @param Definitions $Definitions
     * @param EntityInterface $element
     *
     * @return void
     */
    public function validateData(array $data, Definitions $Definitions, EntityInterface $element) {
        $this->validator = Validator::make($data, []);
        foreach($this->validations as $validation) {
            call_user_func($validation, $this->validator, $Definitions, $element);
        }
        $this->validator->validate($data);
    }
}
