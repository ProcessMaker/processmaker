<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ProcessMaker\Managers\WorkflowManager
 * 
 * @methodStatic mixed callProcess($filename, $processId)
 * @methodStatic mixed triggerStartEvent($definitions, $event, array $data)
 * @methodStatic mixed runScripTask(\ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface $scriptTask, Token $token)
 * @methodStatic mixed runServiceTask(\ProcessMaker\Nayra\Contracts\Bpmn\ServiceTaskInterface $serviceTask, Token $token)
 * @methodStatic void onDataValidation(callable $callback)
 * @methodStatic void validateData(array $data, $definitions, $element)
 */
class WorkflowManager extends Facade
{
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
