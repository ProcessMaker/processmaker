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
                 ->onOneServer()
                 ->withoutOverlapping(config('app.scheduler.bpmn_timer_overlap_minutes', 5));

        $schedule->command('processmaker:sync-recommendations --queue')
                 ->daily()
                 ->onOneServer();

        $schedule->command('package-data-sources:delete-logs')
                 ->weekly();

        $schedule->command('processmaker:sync-default-templates --queue')
                 ->daily();

        $schedule->command('processmaker:sync-screen-templates --queue')
                 ->daily();

        $schedule->command('cache:metrics --format=json > storage/logs/processmaker-cache-metrics.json')
                 ->daily();

        if (class_exists('\ProcessMaker\Packages\Connectors\Slack\Services\SlackNotificationService')) {
            $slackOverDueTaskInterval = config('slack.overdue_tasks.check_interval');
            $slackOverDueTaskBatchSize = config('slack.overdue_tasks.batch_size');

            // Only schedule if interval is valid
            if ($slackOverDueTaskInterval > 0) {
                $slackOverDueTaskCommand = "connector-slack:check-overdue-tasks --batch-size={$slackOverDueTaskBatchSize} --queue";
                $command = $schedule->command($slackOverDueTaskCommand);

                $command->cron("*/{$slackOverDueTaskInterval} * * * *");
            }
        }

        $this->scheduleMetricsClear($schedule);

        // evaluate cases retention policy
        $schedule->command('cases:retention:evaluate')
            ->daily()
            ->onOneServer()
            ->withoutOverlapping()
            ->runInBackground();

        // 5 minutes is recommended in https://laravel.com/docs/12.x/horizon#metrics
        $schedule->command('horizon:snapshot')->everyFiveMinutes();
    }

    protected function scheduleMetricsClear(Schedule $schedule): void
    {
        if (!config('metrics.enabled', false)) {
            return;
        }

        $clearInterval = (int) config('metrics.clear_interval', 1440);

        $event = $schedule->command('metrics:clear --stagger');
        switch ($clearInterval) {
            case 1:
                $event->everyMinute();
                break;
            case 2:
                $event->everyTwoMinutes();
                break;
            case 5:
                $event->everyFiveMinutes();
                break;
            case 10:
                $event->everyTenMinutes();
                break;
            case 15:
                $event->everyFifteenMinutes();
                break;
            case 30:
                $event->everyThirtyMinutes();
                break;
            case 60:
                $event->hourly();
                break;
            case 1440:
                $event->daily();
                break;
            default:
                $event->cron("*/{$clearInterval} * * * *");
                break;
        }

        $event->runInBackground()->withoutOverlapping($clearInterval);
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
