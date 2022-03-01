<?php

namespace ProcessMaker\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\BpmnTimer::class,
        Commands\BuildScriptExecutors::class,
        Commands\Check::class,
        Commands\DataSchema::class,
        Commands\GarbageCollector::class,
        Commands\GenerateSdk::class,
        Commands\IndexedSearchDisable::class,
        Commands\IndexedSearchEnable::class,
        Commands\Install::class,
        Commands\MigrateFresh::class,
        Commands\ProcessmakerClearRequests::class,
        Commands\ProcessMakerTest::class,
        Commands\ProcessMakerValidateProcesses::class,
        Commands\RegenerateCss::class,
        Commands\RetryScriptTasks::class,
        Commands\UnblockRequest::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('bpmn:timer')
                 ->everyMinute()
                 ->onOneServer();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        //
        // Note:
        // The load() method is no longer used as our commands are manually registered
        // to the static $commands property here in the console kernel. This is so the
        // "Upgrade" subdirectory of commands is not automatically registered as they
        // are registered in the UpgradeServiceProvider, which allows for specific
        // functionality otherwise not available
        //
        // $this->load(__DIR__ . '/Commands');
        //

        require base_path('routes/console.php');
    }
}
