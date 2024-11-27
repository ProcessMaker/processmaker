<?php

namespace ProcessMaker\Services;

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\Redis;

class MetricsService
{
    private $registry;

    /**
     * Initializes the MetricsService with a CollectorRegistry using the provided storage adapter.
     *
     * @param mixed $adapter The storage adapter to use (e.g., Redis).
     */
    public function __construct($adapter = null)
    {
        // Set up Redis as the adapter if none is provided
        if ($adapter === null) {
            $adapter = new Redis([
                'host' => env('REDIS_HOST', '127.0.0.1'), 
                'port' => env('REDIS_PORT', '6379')
            ]);
        }
        $this->registry = new CollectorRegistry($adapter);
    }

    /**
     * Returns the CollectorRegistry used by the MetricsService.
     *
     * @return \Prometheus\CollectorRegistry The CollectorRegistry used by the MetricsService.
     */
    public function getMetrics()
    {
        return $this->registry;
    }

    /**
     * Retrieves a metric by its name.
     *
     * This method iterates through all registered metrics and returns the first metric that matches the given name.
     * If no metric with the specified name is found, it returns null.
     *
     * @param string $name The name of the metric to retrieve.
     * @return \Prometheus\MetricFamilySamples|null The metric with the specified name, or null if not found.
     */
    public function getMetricByName(string $name)
    {
        $metrics = $this->registry->getMetricFamilySamples();
        foreach ($metrics as $metric) {
            if ($metric->getName() === $name) {
                return $metric;
            }
        }
        return null;
    }

    /**
     * Registers a new counter metric.
     *
     * @param string $name The name of the counter.
     * @param string $help The help text of the counter.
     * @param array $labels The labels of the counter.
     * @return \Prometheus\Counter The registered counter.
     */
    public function registerCounter(string $name, string $help, array $labels = [])
    {
        return $this->registry->registerCounter('app', $name, $help, $labels);
    }

    /**
     * Registers a new histogram metric.
     *
     * @param string $name The name of the histogram.
     * @param string $help The help text of the histogram.
     * @param array $labels The labels of the histogram.
     * @param array $buckets The buckets of the histogram.
     * @return \Prometheus\Histogram The registered histogram.
     */
    public function registerHistogram(string $name, string $help, array $labels = [], array $buckets = [])
    {
        return $this->registry->registerHistogram('app', $name, $help, $labels, $buckets);
    }

    /**
     * Increments a counter metric by 1.
     *
     * @param string $name The name of the counter.
     * @param array $labelValues The values of the labels for the counter.
     */
    public function incrementCounter(string $name, array $labelValues = [])
    {
        $counter = $this->registry->getCounter('app', $name);
        $counter->inc($labelValues);
    }

    /**
     * Renders the metrics in the Prometheus text format.
     *
     * @return string The rendered metrics.
     */
    public function renderMetrics()
    {
        $renderer = new RenderTextFormat();
        $metrics = $this->registry->getMetricFamilySamples();
        return $renderer->render($metrics);
    }
}
