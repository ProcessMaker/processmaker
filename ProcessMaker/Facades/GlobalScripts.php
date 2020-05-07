<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;
use ProcessMaker\Managers\GlobalScriptsManager;

/**
 * @see \ProcessMaker\Managers\WorkflowManager
 * 
 * @method mixed callProcess($filename, $processId)
 * @method mixed triggerStartEvent($definitions, $event, array $data)
 * @method mixed runScripTask(\ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface $scriptTask, Token $token)
 * @method mixed runServiceTask(\ProcessMaker\Nayra\Contracts\Bpmn\ServiceTaskInterface $serviceTask, Token $token)
 * @method void onDataValidation(callable $callback)
 * @method void validateData(array $data, $definitions)
 */
class GlobalScripts extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return GlobalScriptsManager::class;
    }
}
