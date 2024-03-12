<?php

namespace ProcessMaker\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Jobs\SyncDefaultTemplates;
use ProcessMaker\Jobs\SyncWizardTemplates;

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
        $schedule->command('bpmn:timer')
            ->everyMinute()
            ->onOneServer();

        $schedule->command('package-data-sources:delete-logs')
            ->weekly();

        $schedule->command('processmaker:sync-default-templates --queue')->daily();

        $schedule->command('processmaker:sync-guided-templates --queue')->daily();

        $schedule->command('processmaker:sync-screen-templates --queue')->daily();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
