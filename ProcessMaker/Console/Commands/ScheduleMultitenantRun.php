<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Facades\Metrics;
use ProcessMaker\Multitenancy\Tenant;
use Throwable;

class ScheduleMultitenantRun extends Command
{
    public const LOCK_KEY = 'schedule-multitenant-run';

    public const LOCK_SECONDS = 600; // 10 minutes

    public const DURATION_METRIC = 'schedule_multitenant_run_duration_seconds';

    /**
     * Histogram buckets in seconds, up to the command lock timeout.
     */
    public const DURATION_BUCKETS = [1, 5, 15, 30, 60, 120, 300, 600];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:multitenant-run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the scheduled commands for each tenant';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_SECONDS);

        if (!$lock->get()) {
            $message = 'schedule:multitenant-run is already running; skipping this invocation.';
            Log::error($message);
            $this->error($message);

            return self::FAILURE;
        }

        $startedAt = microtime(true);

        try {
            return $this->runSchedules();
        } catch (Throwable $e) {
            Log::error('schedule:multitenant-run failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            throw $e;
        } finally {
            $lock->release();
        }
    }

    private function runSchedules(): int
    {
        if (config('app.multitenancy') === false) {
            return $this->call('schedule:run');
        }

        $tenants = Tenant::query()->cursor();

        $ranForTenant = false;

        foreach ($tenants as $tenant) {
            $ranForTenant = true;
            $this->info("Running schedule for tenant [{$tenant->id}] {$tenant->name}");

            $tenant->makeCurrent();

            try {
                // Schedule holds sticky CacheEventMutex/CacheSchedulingMutex instances.
                // Forget it after makeCurrent so locks use this tenant's cache prefix.
                // $this->forgetSchedule();
                $this->call('schedule:run');
            } finally {
                Tenant::forgetCurrent();
                // $this->forgetSchedule();
            }
        }

        if (!$ranForTenant) {
            $this->info('No tenants found.');
        }

        return self::SUCCESS;
    }

    /**
     * Drop the Schedule singleton so the next resolve gets fresh mutexes/cache bindings.
     */
    private function forgetSchedule(): void
    {
        app()->forgetInstance(Schedule::class);
    }
}
