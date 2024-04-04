<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;
use ProcessMaker\Bpmn\Process;

/**
 * @see \ProcessMaker\Managers\WorkflowManager
 *
 * @method static mixed callProcess($filename, $processId)
 * @method static mixed triggerStartEvent($definitions, $event, array $data)
 * @method static mixed completeTask(\ProcessMaker\Models\Process $definitions, \ProcessMaker\Nayra\Contracts\Bpmn\ExecutionInstanceInterface $instance, \ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface $token, array $data)
 * @method static mixed taskFailed(\ProcessMaker\Nayra\Contracts\Bpmn\ExecutionInstanceInterface $instance, \ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface $token, string $error)
 * @method static mixed runScripTask(\ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface $scriptTask, Token $token)
 * @method static mixed runServiceTask(\ProcessMaker\Nayra\Contracts\Bpmn\ServiceTaskInterface $serviceTask, Token $token)
 * @method static void throwSignalEventDefinition(\ProcessMaker\Nayra\Contracts\Bpmn\EventDefinitionInterface $sourceEventDefinition, \ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface $token)
 * @method static void throwSignalEvent($signalRef, array $data = [], array $exclude = [])
 * @method static void throwSignalEventProcess($processId, $signalRef, array $data)
 * @method static void throwSignalEventRequest(\ProcessMaker\Models\ProcessRequest $request, $signalRef, array $data)
 * @method static void throwMessageEvent($instanceId, $elementId, $messageRef, array $payload = [])
 * @method static void onDataValidation(callable $callback)
 * @method static void validateData(array $data, $definitions, $element)
 * @method static array runProcess(Process $process, $startEventId, array $data)
 * @method static bool registerServiceImplementation($implementation, $class)
 * @method static bool existsServiceImplementation($implementation)
 * @method static bool runServiceImplementation($implementation, array $data, array $config, $tokenId = '')
 */
class WorkflowManager extends Facade
{
    const NAYRA_PUBLISHER = 'nayra.publisher:';

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'workflow.manager';
    }
}
