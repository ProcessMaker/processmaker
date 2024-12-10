<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessMaker\Services\MetricsService;
use Prometheus\Storage\InMemory;

class MetricsServiceTest extends TestCase
{
    protected $metricsService;

    /**
     * This method is called before each test is executed. 
     * It initializes the MetricsService with an InMemory adapter 
     * to facilitate testing of metrics registration and incrementing.
     * 
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Usar InMemory en lugar de Redis para pruebas
        $adapter = new InMemory();
        $this->metricsService = new MetricsService($adapter);
    }

    /**
     * Test to check if a counter can be registered and incremented.
     * 
     * @return void
     */
    public function test_can_register_and_increment_counter()
    {
        $this->metricsService->registerCounter('test_counter', 'A test counter');
        $this->metricsService->incrementCounter('test_counter');

        $metric = $this->metricsService->getMetricByName('app_test_counter');
        $this->assertNotNull($metric);
        $this->assertEquals(1, $metric->getSamples()[0]->getValue());
    }

    /**
     * Test to check if metrics can be rendered.
     * 
     * @return void
     */
    public function test_can_render_metrics()
    {
        // Register a counter and increment it
        $this->metricsService->registerCounter('render_test_counter', 'Test render metrics');
        $this->metricsService->incrementCounter('render_test_counter');

        // Render the metrics
        $output = $this->metricsService->renderMetrics();

        // Verify that the output contains the expected metric
        $this->assertStringContainsString('render_test_counter', $output);
    }
}
