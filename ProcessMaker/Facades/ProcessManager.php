<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for the Process Manager
 *
 * @package ProcessMaker\Facades
 * @see \ProcessMaker\Managers\ProcessManager
 *
 * @method array index($filter, $start, $limit)
 */
class ProcessManager extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'process.manager';
    }
}
