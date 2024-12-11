<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessMaker\Services\MetricsService;
use Prometheus\CollectorRegistry;
use Prometheus\Histogram;
use Prometheus\Gauge;
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

        // Use InMemory instead of Redis for testing.
        $adapter = new InMemory();
        $this->metricsService = new MetricsService($adapter);
    }

    public function test_set_registry()
    {
        $mockRegistry = $this->createMock(CollectorRegistry::class);

        $this->metricsService->setRegistry($mockRegistry);

        $currentRegistry = $this->metricsService->getMetrics();

        $this->assertSame($mockRegistry, $currentRegistry, "The registry should be updated to the mock registry.");
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

    /**
     * Summary of test_register_histogram.
     * 
     * @return void
     */
    public function test_register_histogram()
    {
        $name = 'test_histogram';
        $help = 'This is a test histogram';
        $labels = ['label1', 'label2'];
        $buckets = [0.1, 1, 5, 10];

        $histogram = $this->metricsService->registerHistogram($name, $help, $labels, $buckets);

        $this->assertInstanceOf(Histogram::class, $histogram);
        $this->assertEquals('app_' . $name, $histogram->getName());
        $this->assertEquals($help, $histogram->getHelp());
    }

    /**
     * Test to check if a gauge can be registered.
     * 
     * @return void
     */
    public function test_register_gauge()
    {
        $name = 'test_gauge';
        $help = 'This is a test gauge';
        $labels = ['label1'];

        $gauge = $this->metricsService->registerGauge($name, $help, $labels);

        $this->assertInstanceOf(Gauge::class, $gauge);
        $this->assertEquals('app_' . $name, $gauge->getName());
        $this->assertEquals($help, $gauge->getHelp());
    }

    /**
     * Test to check if a gauge can be set to a specific value.
     * 
     * @return void
     */
    public function test_set_gauge()
    {
        $name = 'test_set_gauge';
        $this->metricsService->registerGauge($name, 'A test gauge', ['label1']);

        $this->metricsService->setGauge($name, 42, ['value1']);

        $metric = $this->metricsService->getMetricByName('app_' . $name);
        $this->assertNotNull($metric);
        $this->assertEquals(42, $metric->getSamples()[0]->getValue());
    }

    /**
     * Test to check if a histogram can observe a value.
     * 
     * @return void
     */
    public function test_observe_histogram()
    {
        $name = 'test_histogram_observe';
        $this->metricsService->registerHistogram($name, 'A test histogram', ['label1'], [0.1, 1, 5]);

        $this->metricsService->observeHistogram($name, 3.5, ['value1']);

        $metric = $this->metricsService->getMetricByName('app_' . $name);
        $this->assertNotNull($metric);
        $this->assertEquals(0, $metric->getSamples()[1]->getValue());
    }
}
