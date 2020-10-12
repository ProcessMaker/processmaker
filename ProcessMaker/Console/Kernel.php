<?php
namespace ProcessMaker\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use ProcessMaker\Managers\TaskSchedulerManager;
use ProcessMaker\Managers\WorkflowEventManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ScheduledTask;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // @todo Add our ProcessMaker specific commands to this list
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function() {
            $scheduleManager = new TaskSchedulerManager();
            $scheduleManager->scheduleTasks();
        })->everyMinute();
        $schedule->call(function() {
            $scheduleManager = new TaskSchedulerManager();
            $scheduleManager->evaluateConditionals();
        })->everyMinute();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
