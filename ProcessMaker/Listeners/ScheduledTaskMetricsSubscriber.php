<?php

namespace ProcessMaker\Listeners;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Event;
use ProcessMaker\Console\Scheduling\FastCommandEvent;
use ProcessMaker\Facades\Metrics;
use ProcessMaker\Multitenancy\Tenant;
use ProcessMaker\Services\MetricsService;
use WeakMap;

class ScheduledTaskMetricsSubscriber
{
    public const LAST_SUCCESS_TIMESTAMP = 'schedule_job_last_success_timestamp';

    public const LAST_FAILURE_TIMESTAMP = 'schedule_job_last_failure_timestamp';

    public const DURATION_SECONDS = 'schedule_job_duration_seconds';

    public const RUNS_TOTAL = 'schedule_job_runs_total';

    /**
     * @var WeakMap<Event, float>
     */
    private WeakMap $startedAt;

    public function __construct()
    {
        $this->startedAt = new WeakMap();
    }

    public function handleStarting(ScheduledTaskStarting $event): void
    {
        $this->startedAt[$event->task] = microtime(true);

        if (Tenant::current() !== null) {
            Metrics::clearResolvedInstance(MetricsService::class);
            app()->forgetInstance(MetricsService::class);
        }
    }

    public function handleFinished(ScheduledTaskFinished $event): void
    {
        $job = $this->jobName($event->task);

        Metrics::gauge(
            self::DURATION_SECONDS,
            'Duration of the last scheduled job run in seconds',
            ['job']
        )->set($event->runtime, [$job]);

        unset($this->startedAt[$event->task]);

        if ($event->task->runInBackground || $event->task->exitCode !== 0) {
            return;
        }

        $this->recordResult($job, 'success');
    }

    public function handleFailed(ScheduledTaskFailed $event): void
    {
        $job = $this->jobName($event->task);

        if (isset($this->startedAt[$event->task])) {
            Metrics::gauge(
                self::DURATION_SECONDS,
                'Duration of the last scheduled job run in seconds',
                ['job']
            )->set(microtime(true) - $this->startedAt[$event->task], [$job]);

            unset($this->startedAt[$event->task]);
        }

        $this->recordResult($job, 'failure');
    }

    private function recordResult(string $job, string $status): void
    {
        $timestampMetric = $status === 'success'
            ? self::LAST_SUCCESS_TIMESTAMP
            : self::LAST_FAILURE_TIMESTAMP;
        $resultDescription = $status === 'success' ? 'successful' : 'failed';

        Metrics::gauge(
            $timestampMetric,
            "Unix timestamp of the last {$resultDescription} scheduled job run",
            ['job']
        )->set(now()->timestamp, [$job]);

        Metrics::counter(
            self::RUNS_TOTAL,
            'Total number of scheduled job runs',
            ['job', 'status']
        )->inc([$job, $status]);
    }

    private function jobName(Event $task): string
    {
        if ($task instanceof FastCommandEvent) {
            return $task->artisanCommandName();
        }

        if (preg_match('/(?:^|\s)[\'"]?artisan[\'"]?\s+([^\s\'"]+)/', $task->command, $matches)) {
            return $matches[1];
        }

        return $task->getSummaryForDisplay();
    }
}
