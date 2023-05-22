<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;
use ProcessMaker\Models\ProcessRequestToken;

/**
 * @method static int escalateToManager(ProcessRequestToken $task, int $userId)
 */
class WorkflowUserManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'workflow.UserManager';
    }
}
