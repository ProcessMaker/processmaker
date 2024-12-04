<?php

namespace Tests\Unit\ProcessMaker\Console\Commands;

use Mockery;
use ProcessMaker\Cache\Monitoring\CacheMetricsInterface;
use ProcessMaker\Console\Commands\CacheMetricsClearCommand;
use Tests\TestCase;

class CacheMetricsClearCommandTest extends TestCase
{
    protected $metricsManager;

    protected $command;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock for the metrics interface
        $this->metricsManager = Mockery::mock(CacheMetricsInterface::class);

        // Bind the mock to the service container
        $this->app->instance(CacheMetricsInterface::class, $this->metricsManager);

        // Create the command with the mocked dependency
        $this->command = new CacheMetricsClearCommand($this->metricsManager);
    }

    public function testClearMetrics()
    {
        // Set up expectations
        $this->metricsManager->shouldReceive('resetMetrics')
            ->once();

        // Execute the command
        $this->artisan('cache:metrics-clear')
            ->expectsOutput('Clearing all cache metrics data...')
            ->expectsOutput('Cache metrics data cleared successfully!')
            ->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
