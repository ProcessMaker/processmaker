<?php

namespace ProcessMaker\Facades;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Facade;
use ProcessMaker\Models\Script;

/**
 * Facade for our Task Manager
 *
 * @package ProcessMaker\Facades
 * @see \ProcessMaker\Managers\ScriptManager
 *
 * @method static Paginator index(array $options)
 * @method static Script save(array $data)
 * @method static array update(Script $script, array $data)
 * @method static boolean|null remove(Script $script)
 * @method static array getScript()
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
