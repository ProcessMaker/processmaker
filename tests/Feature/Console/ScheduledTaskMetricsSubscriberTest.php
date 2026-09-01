<?php

namespace Tests\Feature\Console;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use ProcessMaker\Console\Scheduling\FastSchedule;
use ProcessMaker\Facades\Metrics;
use ProcessMaker\Listeners\ScheduledTaskMetricsSubscriber;
use ProcessMaker\Multitenancy\Tenant;
use ProcessMaker\Services\MetricsService;
use Prometheus\Storage\InMemory;
use ReflectionProperty;
use RuntimeException;
use Tests\TestCase;

class ScheduledTaskMetricsSubscriberTest extends TestCase
{
    public function setUpMetrics(): void
    {
        config(['app.multitenancy' => false]);

        App::instance(MetricsService::class, new MetricsService(new InMemory()));
    }

    public function testRecordsSuccessfulScheduledTaskMetrics(): void
    {
        $task = (new FastSchedule())->command('emails:send --queue');

        Event::dispatch(new ScheduledTaskStarting($task));
        $task->exitCode = 0;
        Event::dispatch(new ScheduledTaskFinished($task, 2.41));

        $metrics = Metrics::renderMetrics();

        $this->assertStringContainsString(
            ScheduledTaskMetricsSubscriber::LAST_SUCCESS_TIMESTAMP . '{job="emails:send"}',
            $metrics
        );
        $this->assertStringContainsString(
            ScheduledTaskMetricsSubscriber::DURATION_SECONDS . '{job="emails:send"} 2.41',
            $metrics
        );
        $this->assertStringContainsString(
            ScheduledTaskMetricsSubscriber::RUNS_TOTAL . '{job="emails:send",status="success"} 1',
            $metrics
        );
    }

    public function testRecordsFailedScheduledTaskMetricsAndDuration(): void
    {
        $task = (new FastSchedule())->command('emails:send');

        Event::dispatch(new ScheduledTaskStarting($task));
        Event::dispatch(new ScheduledTaskFailed($task, new RuntimeException('Sending failed')));

        $metrics = Metrics::renderMetrics();

        $this->assertStringContainsString(
            ScheduledTaskMetricsSubscriber::LAST_FAILURE_TIMESTAMP . '{job="emails:send"}',
            $metrics
        );
        $this->assertStringContainsString(
            ScheduledTaskMetricsSubscriber::DURATION_SECONDS . '{job="emails:send"}',
            $metrics
        );
        $this->assertStringContainsString(
            ScheduledTaskMetricsSubscriber::RUNS_TOTAL . '{job="emails:send",status="failure"} 1',
            $metrics
        );
        $this->assertStringNotContainsString(
            ScheduledTaskMetricsSubscriber::LAST_SUCCESS_TIMESTAMP . '{job="emails:send"}',
            $metrics
        );
    }

    public function testNonZeroExitIsOnlyRecordedAsFailure(): void
    {
        $task = (new FastSchedule())->command('emails:send');

        Event::dispatch(new ScheduledTaskStarting($task));
        $task->exitCode = 1;
        Event::dispatch(new ScheduledTaskFinished($task, 1.25));
        Event::dispatch(new ScheduledTaskFailed($task, new RuntimeException('Exit code 1')));

        $metrics = Metrics::renderMetrics();

        $this->assertStringContainsString(
            ScheduledTaskMetricsSubscriber::RUNS_TOTAL . '{job="emails:send",status="failure"} 1',
            $metrics
        );
        $this->assertStringNotContainsString(
            ScheduledTaskMetricsSubscriber::RUNS_TOTAL . '{job="emails:send",status="success"}',
            $metrics
        );
    }

    public function testTenantMetricsServiceIsResolvedAfterTenantBecomesCurrent(): void
    {
        config(['app.multitenancy' => true]);
        app()->instance(config('multitenancy.current_tenant_container_key'), new Tenant(['id' => 123]));

        app(ScheduledTaskMetricsSubscriber::class)->handleStarting(
            new ScheduledTaskStarting((new FastSchedule())->command('emails:send'))
        );

        $instances = (new ReflectionProperty(Container::class, 'instances'))->getValue(app());
        $this->assertArrayNotHasKey(MetricsService::class, $instances);

        app()->forgetInstance(config('multitenancy.current_tenant_container_key'));
    }
}
