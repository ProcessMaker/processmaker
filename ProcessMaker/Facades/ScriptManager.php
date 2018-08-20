<?php

namespace ProcessMaker\Facades;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Facade;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Script;

/**
 * Facade for our Task Manager
 *
 * @package ProcessMaker\Facades
 * @see \ProcessMaker\Managers\ScriptManager
 *
 * @method static Paginator index(Process $process, array $options)
 * @method static Script save(Process $process, array $data)
 * @method static array update(Process $process, Script $script, array $data)
 * @method static boolean|null remove(Script $script)
 * @method static array getScript(Process $process)
 *
 */
class ScriptManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'script.manager';
    }
}
