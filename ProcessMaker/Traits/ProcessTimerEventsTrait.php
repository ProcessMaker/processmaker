<?php

namespace ProcessMaker\Traits;

use DOMElement;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Exception\TaskDoesNotHaveUsersException;
use ProcessMaker\Managers\TaskSchedulerManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Providers\WorkflowServiceProvider as PM;
use ProcessMaker\Nayra\Storage\BpmnDocument;

/**
 * Update the task assignments
 *
 * @package ProcessMaker\Traits
 */
trait ProcessTimerEventsTrait
{

    public static function bootProcessTimerEventsTrait()
    {
        static::saved([static::class, 'saveStartEvents']);
        static::saving([static::class, 'convertBPMN']);
    }

    public static function saveStartEvents(Process $process)
    {
        $manager = new TaskSchedulerManager();
        $manager->registerTimerEvents($process->refresh());
    }

    public static function convertBPMN(Process $process)
    {
        $process->convertFromExternalBPM();
    }
}
