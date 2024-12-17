<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessMaker\Services\MetricsService;
use Prometheus\Storage\InMemory;

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
        // Use InMemory storage for testing
        $adapter = new InMemory();
        $this->metricsService = new MetricsService($adapter);
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
}
