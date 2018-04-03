<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for the Calendar Manager
 *
 * @package ProcessMaker\Facades
 * @see \ProcessMaker\Managers\CalendarManager
 *
 */
class CalendarManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'calendar.manager';
    }

}