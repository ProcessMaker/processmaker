<?php

namespace ProcessMaker\Traits;

use DOMElement;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Exception\TaskDoesNotHaveUsersException;
use ProcessMaker\Managers\TaskSchedulerManager;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use ProcessMaker\Providers\WorkflowServiceProvider as PM;

/**
 * Update the task assignments
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
        $manager->registerTimerEvents($process);
    }

    public static function convertBPMN(Process $process)
    {
        $process->convertFromExternalBPM();
    }
}
