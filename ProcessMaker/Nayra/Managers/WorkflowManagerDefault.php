<?php

namespace ProcessMaker\Nayra\Managers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use ProcessMaker\BpmnEngine;
use ProcessMaker\Contracts\ServiceTaskImplementationInterface;
use ProcessMaker\Contracts\WorkflowManagerInterface;
use ProcessMaker\Jobs\BoundaryEvent;
use ProcessMaker\Jobs\CallProcess;
use ProcessMaker\Jobs\CatchEvent;
use ProcessMaker\Jobs\CatchSignalEventInRequest;
use ProcessMaker\Jobs\CatchSignalEventProcess;
use ProcessMaker\Jobs\CompleteActivity;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Jobs\RunServiceTask;
use ProcessMaker\Jobs\StartEvent;
use ProcessMaker\Jobs\ThrowMessageEvent;
use ProcessMaker\Jobs\ThrowSignalEvent;
use ProcessMaker\Models\FormalExpression;
use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken as Token;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\BoundaryEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\CallActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\EntityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\EventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ServiceTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\StartEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ThrowEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;

class WorkflowManagerDefault implements WorkflowManagerInterface
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
     * @var \Illuminate\Contracts\Validation\Validator
     */
    protected $validator;

    /**
     * Service Task implementations
     *
     * @var array
     */
    protected $serviceTaskImplementations = [];

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
        CompleteActivity::dispatchSync($definitions, $instance, $token, $data);
    }

    /**
     * Fail a task.
     *
     * @param ExecutionInstanceInterface $instance
     * @param TokenInterface|ProcessRequestToken $token
     * @param string $error
     *
     * @return void
     */
    public function taskFailed(ExecutionInstanceInterface $instance, TokenInterface $token, string $message)
    {
        $element = $token->getOwnerElement();
        $token->setStatus(ScriptTaskInterface::TOKEN_STATE_FAILING);

        $error = $element->getRepository()->createError();
        $error->setName($message);

        $token->setProperty('error', $error);

        Log::error('Script failed: ' . $element->getId() . ' - ' . $message);
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
        CatchEvent::dispatchSync($definitions, $instance, $token, $data);
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
        BoundaryEvent::dispatchSync($definitions, $instance, $token, $boundaryEvent, $data);
    }

    /**
     * Trigger an start event and return the instance.
     *
     * @param Definitions $definitions
     * @param StartEventInterface $event
     * @param array $data
     * @param callable $beforeStart
     *
     * @return ProcessRequest
     */
    public function triggerStartEvent(Definitions $definitions, StartEventInterface $event, array $data, callable $beforeStart = null)
    {
        //Validate data
        $this->validateData($data, $definitions, $event);

        //Schedule BPMN Action
        return (new StartEvent($definitions, $event, $data))->handle();
    }

    /**
     * Start a process instance.
     *
     * @param Definitions $definitions
     * @param ProcessInterface $process
     * @param array $data
     *
     * @return ProcessRequest
     */
    public function callProcess(Definitions $definitions, ProcessInterface $process, array $data)
    {
        //Validate data
        $this->validateData($data, $definitions, $process);

        //Validate user permissions
        //Validate BPMN rules
        //Log BPMN actions
        //Schedule BPMN Action
        return (new CallProcess($definitions, $process, $data))->handle();
    }

    private function runInlineTask(Token $token, $jobClass)
    {
        $instance = $token->getInstance();
        $process = $instance->process;
        $engine = $instance->getEngine();
        $inlineJob = new $jobClass($process, $instance, $token, []);
        $engine->scheduleInlineJob([
            'job' => $inlineJob,
            'context' => [
                'token' => $token,
                'instance' => $instance,
                'element' => $token->getOwnerElement(),
            ],
        ]);
    }

    /**
     * Determine whether a task can safely reuse the current linear execution context.
     */
    private function canRunInlineTask(Token $token, EntityInterface $element): bool
    {
        $instance = $token->getInstance();
        $engine = $instance->getEngine();
        if (!$engine->isInlineTaskExecutionEnabled()) {
            return false;
        }

        // These execution models require isolation across requests or task instances.
        if ($instance->getRawOriginal('process_collaboration_id')
            || $instance->getRawOriginal('parent_request_id')
            || $token->isMultiInstance()) {
            return false;
        }

        // Boundary events depend on normal queued failure and event handling.
        if ($element instanceof ActivityInterface && $element->getBoundaryEvents()->count() > 0) {
            return false;
        }

        $process = $element->getProcess();
        // BPMN participant metadata detects collaboration when request metadata is unavailable.
        if ($process?->getProperty(ProcessInterface::BPMN_PROPERTY_PARTICIPANT)) {
            return false;
        }

        // Keep parent tasks asynchronous before and after child-process execution.
        foreach ($process?->getActivities() ?? [] as $activity) {
            if ($activity instanceof CallActivityInterface) {
                return false;
            }
        }

        // Inline execution must not bypass an explicitly configured service queue.
        if ($element instanceof ServiceTaskInterface) {
            $configuration = json_decode($element->getProperty('config', '{}'), true) ?: [];
            if (($configuration['queue'] ?? 'bpmn') !== 'bpmn') {
                return false;
            }
        }

        $activeTokens = collect($instance->getTokens())
            ->filter(fn ($currentToken) => !in_array(
                $currentToken->getStatus(),
                BpmnEngine::INACTIVE_TOKEN_STATUSES,
                true
            ))
            ->values();

        // Reuse is safe only for the current token in a strictly linear state.
        return $activeTokens->count() === 1
            && (string) $activeTokens->first()->getId() === (string) $token->getId();
    }

    /**
     * Run a script task.
     *
     * @param ScriptTaskInterface $scriptTask
     * @param Token $token
     */
    public function runScripTask(ScriptTaskInterface $scriptTask, Token $token)
    {
        Log::info('Dispatch a script task: ' . $scriptTask->getId() . ' #' . $token->getId());

        if ($this->canRunInlineTask($token, $scriptTask)) {
            $this->runInlineTask($token, RunScriptTask::class);
            return;
        }

        $instance = $token->processRequest;
        $process = $instance->process;
        RunScriptTask::dispatch($process, $instance, $token, []);
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

        if ($this->canRunInlineTask($token, $serviceTask)) {
            $this->runInlineTask($token, RunServiceTask::class);
            return;
        }

        $instance = $token->processRequest;
        $process = $instance->process;
        RunServiceTask::dispatch($process, $instance, $token, []);
    }

    /**
     * Catch a signal event.
     *
     * @param ServiceTaskInterface $serviceTask
     * @param Token $token
     * @deprecated 4.0.15 Use WorkflowManager::throwSignalEventDefinition()
     */
    public function catchSignalEvent(ThrowEventInterface $source = null, EventDefinitionInterface $sourceEventDefinition, TokenInterface $token)
    {
        $this->throwSignalEventDefinition($sourceEventDefinition, $token);
    }

    /**
     * Throw a signal event.
     *
     * @param EventDefinitionInterface $sourceEventDefinition
     * @param Token $token
     */
    public function throwSignalEventDefinition(EventDefinitionInterface $sourceEventDefinition, TokenInterface $token)
    {
        $signalRef = $sourceEventDefinition->getProperty('signal') ?
            $sourceEventDefinition->getProperty('signal')->getId() :
            $sourceEventDefinition->getProperty('signalRef');

        if (!$signalRef) {
            return;
        }

        $requestData = $token->getInstance()->getDataStore()->getData();
        $eventConfig = json_decode($sourceEventDefinition->getProperty('config') ?? null);
        $payload = $eventConfig && $eventConfig->payload ? $eventConfig->payload[0] : null;
        $payloadId = $payload && $payload->id ? $payload->id : null;

        $data = [];

        switch ($payloadId) {
            case 'REQUEST_VARIABLE':
                if ($payload->variable) {
                    $extractedData = Arr::get($requestData, $payload->variable);
                    Arr::set($data, $payload->variable, $extractedData);
                }
                break;
            case 'EXPRESSION':
                $expression = $payload->expression;
                $formalExp = new FormalExpression();
                $formalExp->setLanguage('FEEL');
                $formalExp->setBody($expression);
                $expressionResult = $formalExp($requestData);
                Arr::set($data, $payload->variable, $expressionResult);
                break;
            case 'NONE':
                $data = [];
                break;
            default:
                $data = $requestData;
                break;
        }

        $excludeProcesses = [$token->getInstance()->getModel()->process_id];

        $excludeRequests = $this->getCollaboratingInstanceIds($token->getInstance());
        ThrowSignalEvent::dispatch($signalRef, $data, $excludeProcesses, $excludeRequests);
    }

    /**
     * Retrieves IDs of all instances collaborating with the given instance.
     *
     * This function compiles a list of IDs from execution instances associated
     * with the same process as the input instance, including the instance itself.
     *
     * @param ProcessRequest $instance The instance to find collaborators for.
     * @return int[] Array of collaborating instance IDs.
     */
    protected function getCollaboratingInstanceIds($instance)
    {
        $ids = [];
        $instances = $instance->getProcess()->getEngine()->getExecutionInstances();
        foreach ($instances as $instance) {
            $ids[] = $instance->getId();
        }

        return $ids;
    }

    /**
     * Throw a signal event by id (signalRef).
     *
     * @param string $signalRef
     * @param array $data
     * @param array $exclude
     */
    public function throwSignalEvent($signalRef, array $data = [], array $exclude = [])
    {
        ThrowSignalEvent::dispatch($signalRef, $data, $exclude);
    }

    /**
     * Throw a signal event by signalRef into a specific process.
     *
     * @param int $process
     * @param string $signalRef
     * @param array $data
     */
    public function throwSignalEventProcess($processId, $signalRef, array $data)
    {
        CatchSignalEventProcess::dispatch(
            $processId,
            $signalRef,
            $data
        );
    }

    /**
     * Throw a signal event by signalRef into a specific request.
     *
     * @param ProcessRequest $request
     * @param string $signalRef
     * @param array $data
     */
    public function throwSignalEventRequest(ProcessRequest $request, $signalRef, array $data)
    {
        CatchSignalEventInRequest::dispatchSync(
            $request,
            $data,
            $signalRef
        );
    }

    /**
     * Catch a signal event.
     *
     * @param EventDefinitionInterface $sourceEventDefinition
     * @param Token $token
     */
    public function throwMessageEvent($instanceId, $elementId, $messageRef, array $payload = [])
    {
        ThrowMessageEvent::dispatch($instanceId, $elementId, $messageRef, $payload);
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
    public function validateData(array $data, Definitions $Definitions, EntityInterface $element)
    {
        $this->validator = Validator::make($data, []);
        foreach ($this->validations as $validation) {
            call_user_func($validation, $this->validator, $Definitions, $element);
        }
        $this->validator->validate($data);
    }

    /**
     * Run a process and returns its data
     *
     * @param Definitions $definitions
     * @param string $startId
     * @param array $data
     *
     * @return array
     */
    public function runProcess(Definitions $definitions, $startId, array $data)
    {
        $startEvent = $definitions->getDefinitions()->getStartEvent($startId);
        $instance = $this->triggerStartEvent($definitions, $startEvent, $data);

        return $instance->getDataStore()->getData();
    }

    /**
     * Check if service task implementation exists
     *
     * @param string $implementation
     *
     * @return bool
     */
    public function registerServiceImplementation($implementation, $class)
    {
        if (!class_exists($class)) {
            return false;
        }

        // check class instance of ServiceTaskImplementationInterface
        if (!is_subclass_of($class, ServiceTaskImplementationInterface::class)) {
            Log::error(
                'Service task implementation ' . $class
                . ' must be an instance of '
                . ServiceTaskImplementationInterface::class
            );

            return false;
        }

        $this->serviceTaskImplementations[$implementation] = $class;

        return true;
    }

    /**
     * Check if service task implementation exists
     *
     * @param string $implementation
     *
     * @return bool
     */
    public function existsServiceImplementation($implementation)
    {
        return isset($this->serviceTaskImplementations[$implementation]) &&
            class_exists($this->serviceTaskImplementations[$implementation]);
    }

    /**
     * Run the service task implementation
     *
     * @param string $implementation
     * @param array $data
     * @param array $config
     * @param string $tokenId
     * @return mixed
     */
    public function runServiceImplementation($implementation, array $data, array $config, $tokenId = '', $timeout = 0)
    {
        $class = $this->serviceTaskImplementations[$implementation];
        $service = new $class();

        return $service->run($data, $config, $tokenId, $timeout);
    }

    /**
     * Get the service task class implementation
     *
     * @param string $implementation
     * @return string
     */
    public function getServiceClassImplementation($implementation)
    {
        $class = $this->serviceTaskImplementations[$implementation];

        return $class;
    }
}
