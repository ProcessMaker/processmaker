<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * FormalExpression Facade
 *
 * @see \ProcessMaker\Models\FormalExpression
 */
class FormalExpression extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'workflow.FormalExpression';
    }
}
