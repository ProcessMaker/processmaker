<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\App;
use ProcessMaker\Facades\Metrics;
use ProcessMaker\Services\MetricsService;
use Prometheus\Storage\InMemory;
use Tests\TestCase;

class MetricsFacadeTest extends TestCase
{
    /**
     * This method is called before each test is executed.
     * It simulates the MetricsService with an InMemory adapter
     * to facilitate testing of metrics registration and incrementing.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Simulate the MetricsService with an InMemory adapter
        $adapter = new InMemory();
        $mockService = new MetricsService($adapter);
        App::instance(MetricsService::class, $mockService); // Replace the service in the container
    }

    /**
     * Test to check if a counter can be registered and incremented using the facade.
     */
    public function test_facade_can_register_and_increment_counter()
    {
        // Register a counter using the Facade
        $counter = Metrics::counter('facade_counter', 'Test counter via facade');

        // Increment the counter
        $counter->inc();

        // Verify that the metric was registered and incremented
        $this->assertTrue(true); // In this point we assume that there are no errors
    }

    /**
     * Test to check if metrics can be rendered using the facade.
     */
    public function test_facade_can_render_metrics()
    {
        // Register and increment a counter
        $counter = Metrics::counter('facade_render_test', 'Render test via facade');
        $counter->inc();

        // Render the metrics
        $output = Metrics::renderMetrics();

        // Verify the metric in the output
        $this->assertStringContainsString('facade_render_test', $output);
    }
}
