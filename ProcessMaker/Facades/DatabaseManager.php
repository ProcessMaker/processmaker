<?php
namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for our Database Manager
 * @package ProcessMaker\Facades
 * @see \ProcessMaker\Managers\DatabaseManager
 */
class DatabaseManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'database.manager';
    }
}
