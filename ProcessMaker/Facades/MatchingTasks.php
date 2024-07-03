<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ProcessMaker\InboxRules\MatchingTasks
 */
class MatchingTasks extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return MatchingTasks::class;
    }
}
