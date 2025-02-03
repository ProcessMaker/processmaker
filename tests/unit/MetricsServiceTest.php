<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Mockery;
use ProcessMaker\Facades\Metrics;
use ProcessMaker\Services\MetricsService;
use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Histogram;
use Prometheus\Storage\InMemory;
use ReflectionClass;
use Tests\TestCase;


class MetricsServiceTest extends TestCase
{
    /**
     * The MetricsService instance used by the test.
     * @var MetricsService
     */
    private $metricsService;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Use InMemory storage for testing
        $adapter = new InMemory();
        $this->metricsService = new MetricsService($adapter);
        App::instance(MetricsService::class, $this->metricsService); // Replace the service in the container
    }

    /**
     * Test the counter registration and increment.
     */
    public function testCounterRegistrationAndIncrement(): void
    {
        $counter = $this->metricsService->counter('test_counter', 'Test Counter', ['label1']);

        // Assert the counter is registered
        $this->assertInstanceOf(\Prometheus\Counter::class, $counter);

        // Increment the counter and assert the value
        $counter->inc(['value1']);
        $samples = $this->metricsService->renderMetrics();
        $this->assertStringContainsString('test_counter', $samples);
        $this->assertStringContainsString('value1', $samples);
    }

    /**
     * Test the gauge registration and set.
     */
    public function testGaugeRegistrationAndSet(): void
    {
        $gauge = $this->metricsService->gauge('test_gauge', 'Test Gauge', ['label1']);

        // Assert the gauge is registered
        $this->assertInstanceOf(\Prometheus\Gauge::class, $gauge);

        // Set the gauge value and assert the value
        $gauge->set(10, ['value1']);
        $samples = $this->metricsService->renderMetrics();
        $this->assertStringContainsString('test_gauge', $samples);
        $this->assertStringContainsString('10', $samples);
    }

    /**
     * Test the histogram registration and observe.
     */
    public function testHistogramRegistrationAndObserve(): void
    {
        $histogram = $this->metricsService->histogram(
            'test_histogram',
            'Test Histogram',
            ['label1'],
            [0.1, 1, 5]
        );

        // Assert the histogram is registered
        $this->assertInstanceOf(\Prometheus\Histogram::class, $histogram);

        // Observe a value and assert it is recorded
        $histogram->observe(0.5, ['value1']);
        $samples = $this->metricsService->renderMetrics();
        $this->assertStringContainsString('test_histogram', $samples);
        $this->assertStringContainsString('0.5', $samples);
    }

    /**
     * Test the renderMetrics method.
     */
    public function testRenderMetrics(): void
    {
        $counter = $this->metricsService->counter('render_test', 'Render Test Counter', ['label']);
        $counter->inc(['value1']);

        $metrics = $this->metricsService->renderMetrics();
        $this->assertStringContainsString('render_test', $metrics);
    }

    /**
     * Test the default namespace.
     */
    public function testDefaultNamespace(): void
    {
        $counter = $this->metricsService->counter('namespace_test');

        // Assert default namespace is applied
        $this->assertInstanceOf(\Prometheus\Counter::class, $counter);
        $counter->inc();

        $samples = $this->metricsService->renderMetrics();

        $this->assertStringContainsString('namespace_test', $samples);
    }

    /**
     * Test the gauge set method.
     */
    public function testSetGaugeValue(): void
    {
        $this->metricsService->gauge('test_set_gauge', 'Gauge Test', ['label'])->set(5, ['label_value']);
        $samples = $this->metricsService->renderMetrics();

        $this->assertStringContainsString('test_set_gauge', $samples);
        $this->assertStringContainsString('5', $samples);
    }
    /**
     * Test that counterInc calls Metrics::counter() and then inc() with the correct labels.
     */
    public function testCounterInc()
    {
        // Set configuration values used by addSystemLabels()
        Config::set('app.name', 'TestApp');
        Config::set('app.prometheus_custom_label', 'customValue');

        // Create an instance of MetricsService.
        $service = new MetricsService();

        $counterName = 'test_counter';
        $helpText = 'A test counter';
        $initialLabels = ['user' => '123'];

        // Determine what system labels will be added.
        // (We call addSystemLabels with an empty array to extract the system values.)
        $systemLabels = $service->addSystemLabels([]);
        // Merge the initial labels with system labels.
        $expectedLabels = array_merge($initialLabels, [
            'app_version'       => $systemLabels['app_version'],
            'app_name'          => 'TestApp',
            'app_custom_label'  => 'customValue',
        ]);
        $expectedLabelKeys = array_keys($expectedLabels);

        // Create a mock Counter object that expects inc() to be called once.
        $mockCounter = Mockery::mock(Counter::class);
        $mockCounter->shouldReceive('inc')
            ->once()
            ->with($expectedLabels);

        // Expect the Metrics facade to be called with the right parameters
        // and to return our mock Counter.
        Metrics::shouldReceive('counter')
            ->once()
            ->with($counterName, $helpText, $expectedLabelKeys)
            ->andReturn($mockCounter);

        // Call counterInc which should trigger the facade calls.
        $service->counterInc($counterName, $helpText, $initialLabels);
    }

    /**
     * Test that histogramObserve calls Metrics::histogram() and then observe() with the correct values.
     */
    public function testHistogramObserve()
    {
        // Set configuration values used by addSystemLabels()
        Config::set('app.name', 'TestApp');
        Config::set('app.prometheus_custom_label', 'customValue');

        $service = new MetricsService();

        $histogramName = 'test_histogram';
        $helpText = 'A test histogram';
        $initialLabels = ['endpoint' => '/api/test'];
        $buckets = [0.1, 1, 5, 10];
        $executionTime = 2.5;

        // Determine what system labels will be added.
        $systemLabels = $service->addSystemLabels([]);
        $expectedLabels = array_merge($initialLabels, [
            'app_version'       => $systemLabels['app_version'],
            'app_name'          => 'TestApp',
            'app_custom_label'  => 'customValue',
        ]);
        $expectedLabelKeys = array_keys($expectedLabels);

        // Create a mock Histogram that expects observe() to be called once.
        $mockHistogram = Mockery::mock(Histogram::class);
        $mockHistogram->shouldReceive('observe')
            ->once()
            ->with($executionTime, $expectedLabels);

        // Expect the Metrics facade to be called with the right parameters
        // and to return our mock Histogram.
        Metrics::shouldReceive('histogram')
            ->once()
            ->with($histogramName, $helpText, $expectedLabelKeys, $buckets)
            ->andReturn($mockHistogram);

        // Call histogramObserve which should trigger the facade calls.
        $service->histogramObserve($histogramName, $helpText, $initialLabels, $buckets, $executionTime);
    }

    /**
     * Test that addSystemLabels returns the input labels plus the system labels.
     */
    public function testAddSystemLabels()
    {
        // Set configuration values used by addSystemLabels()
        Config::set('app.name', 'TestApp');
        Config::set('app.prometheus_custom_label', 'customValue');

        $service = new MetricsService();

        $inputLabels = ['label1' => 'value1'];
        $result = $service->addSystemLabels($inputLabels);

        // Assert that the original label is preserved.
        $this->assertEquals('value1', $result['label1']);

        // Assert that the system labels were added.
        $this->assertArrayHasKey('app_version', $result);
        $this->assertArrayHasKey('app_name', $result);
        $this->assertArrayHasKey('app_custom_label', $result);
        $this->assertEquals('TestApp', $result['app_name']);
        $this->assertEquals('customValue', $result['app_custom_label']);
        $this->assertNotEmpty($result['app_version']); // Assuming composer.json defines a version.
    }

    /**
     * Test that clearMetrics calls wipeStorage on the collection registry.
     */
    public function testClearMetrics()
    {
        $service = new MetricsService();

        // Create a mock for the CollectorRegistry.
        $mockRegistry = Mockery::mock(CollectorRegistry::class);
        $mockRegistry->shouldReceive('wipeStorage')
            ->once();

        // Use reflection to override the private property "collectionRegistry" with our mock.
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('collectionRegistry');
        $property->setAccessible(true);
        $property->setValue($service, $mockRegistry);

        // Call clearMetrics which should call wipeStorage on the registry.
        $service->clearMetrics();
    }
}
