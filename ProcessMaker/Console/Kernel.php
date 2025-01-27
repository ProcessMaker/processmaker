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
    protected $commands = [];

    /**
     * Define the application's command schedule.
     *
     * @param  Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('bpmn:timer')
                 ->everyMinute()
                 ->onOneServer();

        $schedule->command('processmaker:sync-recommendations --queue')
                 ->daily()
                 ->onOneServer();

        $schedule->command('package-data-sources:delete-logs')
                 ->weekly();

        $schedule->command('processmaker:sync-default-templates --queue')
                 ->daily();

        $schedule->command('processmaker:sync-guided-templates --queue')
                 ->daily();

        $schedule->command('processmaker:sync-screen-templates --queue')
                 ->daily();

        $schedule->command('cache:metrics --format=json > storage/logs/processmaker-cache-metrics.json')
                 ->daily();
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
