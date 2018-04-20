<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;
use ProcessMaker\Model\Process;

/**
 * Facade for our Task Manager
 *
 * @package ProcessMaker\Facades
 * @see \ProcessMaker\Managers\TriggerManager
 *
 * @method static array getTriggers(Process $process)
 *
 */
class TriggerManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'trigger.manager';
    }
}
