<?php

namespace ProcessMaker\Facades;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Facade;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Trigger;

/**
 * Facade for our Task Manager
 *
 * @package ProcessMaker\Facades
 * @see \ProcessMaker\Managers\TriggerManager
 *
 * @method static Paginator index(Process $process, array $options)
 * @method static boolean|null remove(Trigger $trigger)
 * @method static array getTriggers(Process $process)
 * @method static array save(Process $process, array $data)
 * @method static array update(Process $process, Trigger $trigger, array $data)
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
