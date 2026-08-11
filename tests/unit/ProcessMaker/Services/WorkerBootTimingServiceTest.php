<?php

namespace Tests\Unit\ProcessMaker\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use ProcessMaker\Services\WorkerBootTimingService;
use ProcessMaker\Traits\PluginServiceProviderTrait;
use Tests\TestCase;

class WorkerBootTimingServiceTest extends TestCase
{
    public function test_it_retains_provider_boot_time_for_the_worker_lifetime(): void
    {
        $service = app(WorkerBootTimingService::class);
        $service->setProviderBootTime(12.5);

        $this->app->forgetScopedInstances();

        $this->assertSame($service, app(WorkerBootTimingService::class));
        $this->assertSame(12.5, app(WorkerBootTimingService::class)->getProviderBootTime());
        $this->assertNotContains(WorkerBootTimingService::class, config('octane.flush'));
    }

    public function test_octane_application_clone_shares_the_worker_timing_service(): void
    {
        $service = app(WorkerBootTimingService::class);
        $service->setProviderBootTime(18.75);

        $sandbox = clone $this->app;
        $sandboxService = $sandbox->make(WorkerBootTimingService::class);

        $this->assertSame($service, $sandboxService);
        $this->assertSame(18.75, $sandboxService->getProviderBootTime());
    }

    public function test_it_records_package_boot_start_and_end_times(): void
    {
        $service = new WorkerBootTimingService();

        $service->setPackageBootStart('ExamplePackage', 10.25);
        $service->setPackageBootedTime('ExamplePackage', 10.75);

        $this->assertSame([
            'ExamplePackage' => [
                'start' => 10.25,
                'end' => 10.75,
            ],
        ], $service->getPackageBootTiming());
    }

    public function test_repeated_package_measurements_replace_the_existing_entry(): void
    {
        $service = new WorkerBootTimingService();

        $service->setPackageBootStart('ExamplePackage', 10.0);
        $service->setPackageBootedTime('ExamplePackage', 11.0);
        $service->setPackageBootStart('ExamplePackage', 20.0);
        $service->setPackageBootedTime('ExamplePackage', 20.5);

        $this->assertCount(1, $service->getPackageBootTiming());
        $this->assertSame([
            'start' => 20.0,
            'end' => 20.5,
        ], $service->getPackageBootTiming()['ExamplePackage']);
    }

    public function test_many_repeated_measurements_remain_bounded_by_unique_package_names(): void
    {
        $service = new WorkerBootTimingService();

        for ($index = 0; $index < 1000; $index++) {
            $package = 'Package' . ($index % 5);
            $service->setPackageBootStart($package, (float) $index);
            $service->setPackageBootedTime($package, $index + 0.5);
        }

        $this->assertCount(5, $service->getPackageBootTiming());
        $this->assertSame([
            'start' => 999.0,
            'end' => 999.5,
        ], $service->getPackageBootTiming()['Package4']);
    }

    public function test_returned_package_timing_snapshot_cannot_mutate_worker_state(): void
    {
        $service = new WorkerBootTimingService();
        $service->setPackageBootStart('ExamplePackage', 10.0);
        $service->setPackageBootedTime('ExamplePackage', 10.5);

        $snapshot = $service->getPackageBootTiming();
        $snapshot['ExamplePackage']['start'] = 999.0;
        $snapshot['InjectedPackage'] = [
            'start' => 20.0,
            'end' => 21.0,
        ];

        $this->assertSame([
            'ExamplePackage' => [
                'start' => 10.0,
                'end' => 10.5,
            ],
        ], $service->getPackageBootTiming());
    }

    public function test_separate_worker_services_do_not_share_timing_state(): void
    {
        $firstWorker = new WorkerBootTimingService();
        $secondWorker = new WorkerBootTimingService();

        $firstWorker->setProviderBootTime(10.0);
        $firstWorker->setPackageBootStart('FirstWorkerPackage', 1.0);
        $firstWorker->setPackageBootedTime('FirstWorkerPackage', 1.5);

        $secondWorker->setProviderBootTime(20.0);
        $secondWorker->setPackageBootStart('SecondWorkerPackage', 2.0);
        $secondWorker->setPackageBootedTime('SecondWorkerPackage', 2.5);

        $this->assertSame(10.0, $firstWorker->getProviderBootTime());
        $this->assertSame(20.0, $secondWorker->getProviderBootTime());
        $this->assertArrayHasKey('FirstWorkerPackage', $firstWorker->getPackageBootTiming());
        $this->assertArrayNotHasKey('SecondWorkerPackage', $firstWorker->getPackageBootTiming());
        $this->assertArrayHasKey('SecondWorkerPackage', $secondWorker->getPackageBootTiming());
        $this->assertArrayNotHasKey('FirstWorkerPackage', $secondWorker->getPackageBootTiming());
    }

    public function test_invalid_package_start_time_is_logged_and_clamped_to_zero(): void
    {
        Log::spy();
        $service = new WorkerBootTimingService();

        $service->setPackageBootStart('InvalidPackage', -1.5);

        $this->assertSame([
            'start' => 0.0,
            'end' => null,
        ], $service->getPackageBootTiming()['InvalidPackage']);
        Log::shouldHaveReceived('info')
            ->once()
            ->with('Server Timing: Invalid boot time for package: InvalidPackage, time: -1.5');
    }

    public function test_invalid_package_end_time_is_logged_and_ignored(): void
    {
        Log::spy();
        $service = new WorkerBootTimingService();
        $service->setPackageBootStart('InvalidPackage', 5.0);

        $service->setPackageBootedTime('InvalidPackage', -2.5);

        $this->assertNull($service->getPackageBootTiming()['InvalidPackage']['end']);
        Log::shouldHaveReceived('info')
            ->once()
            ->with('Server Timing: Invalid booted time for package: InvalidPackage, time: -2.5');
    }

    public function test_package_end_without_a_start_is_logged_and_does_not_create_state(): void
    {
        Log::spy();
        $service = new WorkerBootTimingService();

        $service->setPackageBootedTime('UnstartedPackage', 10.5);

        $this->assertSame([], $service->getPackageBootTiming());
        Log::shouldHaveReceived('info')
            ->once()
            ->with('Server Timing: Invalid booted time for package: UnstartedPackage, time: 10.5');
    }

    public function test_plugin_service_provider_records_one_complete_package_interval(): void
    {
        config(['app.server_timing.enabled' => true]);

        $this->app->register(new WorkerBootTimingTestPluginServiceProvider($this->app));

        $timing = app(WorkerBootTimingService::class)->getPackageBootTiming();
        $this->assertArrayHasKey('WorkerBootTimingTestPlugin', $timing);
        $this->assertIsFloat($timing['WorkerBootTimingTestPlugin']['start']);
        $this->assertIsFloat($timing['WorkerBootTimingTestPlugin']['end']);
        $this->assertGreaterThanOrEqual(
            $timing['WorkerBootTimingTestPlugin']['start'],
            $timing['WorkerBootTimingTestPlugin']['end']
        );
    }

    public function test_plugin_service_provider_does_not_record_timing_when_disabled(): void
    {
        config(['app.server_timing.enabled' => false]);

        $this->app->register(new DisabledWorkerBootTimingTestPluginServiceProvider($this->app));

        $timing = app(WorkerBootTimingService::class)->getPackageBootTiming();
        $this->assertArrayNotHasKey('DisabledWorkerBootTimingTestPlugin', $timing);
    }
}

final class WorkerBootTimingTestPluginServiceProvider extends ServiceProvider
{
    use PluginServiceProviderTrait;

    public const name = 'worker-boot-timing-test-plugin';

    public function boot(): void
    {
        usleep(1000);
    }
}

final class DisabledWorkerBootTimingTestPluginServiceProvider extends ServiceProvider
{
    use PluginServiceProviderTrait;

    public const name = 'disabled-worker-boot-timing-test-plugin';
}
