<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ProcessMaker\Managers\WorkflowManager
 * 
 * @method mixed callProcess($filename, $processId)
 * @method mixed triggerStartEvent($definitions, $event, array $data)
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
