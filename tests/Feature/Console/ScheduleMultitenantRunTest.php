<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Console\Commands\ScheduleMultitenantRun;
use ProcessMaker\Facades\Metrics;
use ProcessMaker\Services\MetricsService;
use Prometheus\Histogram;
use Prometheus\Storage\InMemory;
use Tests\TestCase;

class ScheduleMultitenantRunTest extends TestCase
{
    public function setUpMetrics()
    {
        $adapter = new InMemory();
        App::instance(MetricsService::class, new MetricsService($adapter));
    }

    public function testCommandIsRegistered()
    {
        $this->assertTrue(
            array_key_exists('schedule:tenants-run', Artisan::all())
        );
    }

    public function testCommandFallsBackToScheduleRunWhenMultitenancyDisabled()
    {
        config(['app.multitenancy' => false]);

        $this->artisan('schedule:tenants-run')
            ->assertSuccessful();
    }

    public function testCommandSkipsWhenLockIsAlreadyHeld()
    {
        config(['app.multitenancy' => false]);

        $lock = Cache::lock(ScheduleMultitenantRun::LOCK_KEY, ScheduleMultitenantRun::LOCK_SECONDS);
        $this->assertTrue($lock->get());

        try {
            Log::shouldReceive('error')
                ->once()
                ->withArgs(function (string $message) {
                    return str_contains($message, 'already running');
                });

            $this->artisan('schedule:tenants-run')
                ->expectsOutputToContain('already running')
                ->assertFailed();
        } finally {
            $lock->release();
        }
    }

    public function testCommandReleasesLockAfterSuccessfulRun()
    {
        config(['app.multitenancy' => false]);

        $this->artisan('schedule:tenants-run')
            ->assertSuccessful();

        $lock = Cache::lock(ScheduleMultitenantRun::LOCK_KEY, ScheduleMultitenantRun::LOCK_SECONDS);
        $this->assertTrue($lock->get(), 'Lock should be available after a successful run');
        $lock->release();
    }

    public function testCommandRecordsDurationHistogram()
    {
        config(['app.multitenancy' => false]);

        $this->artisan('schedule:tenants-run')
            ->assertSuccessful();

        $ns = config('app.prometheus_namespace', 'app');
        $histogram = Metrics::getCollectionRegistry()->getHistogram(
            $ns,
            ScheduleMultitenantRun::DURATION_METRIC
        );

        $this->assertInstanceOf(Histogram::class, $histogram);
        $this->assertStringContainsString(
            ScheduleMultitenantRun::DURATION_METRIC,
            Metrics::renderMetrics()
        );
    }

    public function testCommandDoesNotRecordDurationWhenLockIsAlreadyHeld()
    {
        config(['app.multitenancy' => false]);

        $lock = Cache::lock(ScheduleMultitenantRun::LOCK_KEY, ScheduleMultitenantRun::LOCK_SECONDS);
        $this->assertTrue($lock->get());

        try {
            Log::shouldReceive('error')
                ->once()
                ->withArgs(function (string $message) {
                    return str_contains($message, 'already running');
                });

            $this->artisan('schedule:tenants-run')
                ->assertFailed();

            $this->assertStringNotContainsString(
                ScheduleMultitenantRun::DURATION_METRIC,
                Metrics::renderMetrics()
            );
        } finally {
            $lock->release();
        }
    }
}
