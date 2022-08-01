<?php

namespace ProcessMaker\Traits;

use ProcessMaker\Managers\TaskSchedulerManager;
use ProcessMaker\Models\Process;

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
