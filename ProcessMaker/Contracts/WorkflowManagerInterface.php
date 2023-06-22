<?php

namespace ProcessMaker\Contracts;

use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Models\ProcessRequestToken as Token;
use ProcessMaker\Nayra\Contracts\Bpmn\BoundaryEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\EntityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\EventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ServiceTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\StartEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ThrowEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;

interface WorkflowManagerInterface
{
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
    public function completeTask(Definitions $definitions, ExecutionInstanceInterface $instance, TokenInterface $token, array $data);

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
    public function completeCatchEvent(Definitions $definitions, ExecutionInstanceInterface $instance, TokenInterface $token, array $data);

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
    public function triggerBoundaryEvent(Definitions $definitions, ExecutionInstanceInterface $instance, TokenInterface $token, BoundaryEventInterface $boundaryEvent, array $data);

    /**
     * Trigger an start event and return the instance.
     *
     * @param Definitions $definitions
     * @param StartEventInterface $event
     *
     * @return \ProcessMaker\Models\ProcessRequest
     */
    public function triggerStartEvent(Definitions $definitions, StartEventInterface $event, array $data);

    /**
     * Start a process instance.
     *
     * @param Definitions $definitions
     * @param ProcessInterface $process
     * @param array $data
     *
     * @return \ProcessMaker\Models\ProcessRequest
     */
    public function callProcess(Definitions $definitions, ProcessInterface $process, array $data);

    /**
     * Run a script task.
     *
     * @param ScriptTaskInterface $scriptTask
     * @param Token $token
     */
    public function runScripTask(ScriptTaskInterface $scriptTask, Token $token);

    /**
     * Run a service task.
     *
     * @param ServiceTaskInterface $serviceTask
     * @param Token $token
     */
    public function runServiceTask(ServiceTaskInterface $serviceTask, Token $token);

    /**
     * Catch a signal event.
     *
     * @param ServiceTaskInterface $serviceTask
     * @param Token $token
     * @deprecated 4.0.15 Use WorkflowManager::throwSignalEventDefinition()
     */
    public function catchSignalEvent(ThrowEventInterface $source = null, EventDefinitionInterface $sourceEventDefinition, TokenInterface $token);

    /**
     * Throw a signal event.
     *
     * @param EventDefinitionInterface $sourceEventDefinition
     * @param Token $token
     */
    public function throwSignalEventDefinition(EventDefinitionInterface $sourceEventDefinition, TokenInterface $token);

    /**
     * Throw a signal event by id (signalRef).
     *
     * @param string $signalRef
     * @param array $data
     * @param array $exclude
     */
    public function throwSignalEvent($signalRef, array $data = [], array $exclude = []);

    /**
     * Catch a signal event.
     *
     * @param EventDefinitionInterface $sourceEventDefinition
     * @param Token $token
     */
    public function throwMessageEvent($instanceId, $elementId, $messageRef, array $payload = []);

    /**
     * Attach validation event
     *
     * @param callable $callback
     * @return void
     */
    public function onDataValidation($callback);

    /**
     * Validate data
     *
     * @param array $data
     * @param Definitions $Definitions
     * @param EntityInterface $element
     *
     * @return void
     */
    public function validateData(array $data, Definitions $Definitions, EntityInterface $element);

    /**
     * Run a process and returns its data
     *
     * @param Definitions $definitions
     * @param string $startId
     * @param array $data
     *
     * @return array
     */
    public function runProcess(Definitions $definitions, $startId, array $data);

    /**
     * Check if service task implementation exists
     *
     * @param string $implementation
     *
     * @return bool
     */
    public function registerServiceImplementation($implementation, $class);

    /**
     * Check if service task implementation exists
     *
     * @param string $implementation
     *
     * @return bool
     */
    public function existsServiceImplementation($implementation);

    /**
     * Run the service task implementation
     * @param string $implementation
     * @param array $dat
     * @param array $config
     * @param string $tokenId
     *
     * @return mixed
     */
    public function runServiceImplementation($implementation, array $data, array $config, $tokenId = '');
}
