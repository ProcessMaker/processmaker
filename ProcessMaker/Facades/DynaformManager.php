<?php

namespace ProcessMaker\Facades;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Facade;
use ProcessMaker\Model\Dynaform;
use ProcessMaker\Model\Process;

/**
 * Facade for our OutPut Document Manager
 *
 * @package ProcessMaker\Facades
 * @see \ProcessMaker\Managers\DynaformManager
 *
 * @method static Paginator index(Process $process)
 * @method static Dynaform save(Process $process, array $data)
 * @method static Dynaform copyImport(Process $process, array $data)
 * @method static Dynaform createBasedPmTable(Process $process, array $data)
 * @method static array update(Process $process, Dynaform $dynaform, array $data)
 * @method static boolean|null remove(Dynaform $dynaform)
 *
 */
class DynaformManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'dynaform.manager';
    }
}