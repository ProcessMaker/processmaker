<?php

namespace ProcessMaker\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\Redis;
use RuntimeException;

class MetricsService
{
    /**
     * The CollectorRegistry instance used by the MetricsService.
     *
     * @var \Prometheus\CollectorRegistry
     */
    private $collectionRegistry;

    /**
     * The namespace used by the MetricsService.
     *
     * @var string
     */
    private $namespace = 'app';

    /**
     * Initializes the MetricsService with a CollectorRegistry using the provided storage adapter.
     * Example:
     * $metricsService = new MetricsService(new Redis([
     *     'host' => config('database.redis.default.host'),
     *     'port' => config('database.redis.default.port'),
     * ]));
     *
     * @param mixed $adapter The storage adapter to use (e.g., Redis).
     */
    public function __construct($adapter = null)
    {
        try {
            // Set up Redis as the adapter if none is provided
            if ($adapter === null) {
                $adapter = new Redis([
                    'host' => config('database.redis.default.host'),
                    'port' => config('database.redis.default.port'),
                ]);
            }
            $this->collectionRegistry = new CollectorRegistry($adapter);
        } catch (Exception $e) {
            throw new RuntimeException('Error initializing the metrics adapter: ' . $e->getMessage());
        }
    }

    /**
     * Returns the namespace used by the MetricsService.
     *
     * @return string The namespace used by the MetricsService.
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Sets the namespace used by the MetricsService.
     *
     * @param string $namespace The namespace to set.
     */
    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    /**
     * Sets the CollectorRegistry used by the MetricsService.
     * Example:
     * $metricsService->setRegistry(new CollectorRegistry(new Redis([
     *     'host' => config('database.redis.default.host'),
     *     'port' => config('database.redis.default.port'),
     * ])));
     *
     * @param \Prometheus\CollectorRegistry $collectionRegistry The CollectorRegistry to set.
     */
    public function setRegistry(CollectorRegistry $collectionRegistry): void
    {
        $this->collectionRegistry = $collectionRegistry;
    }

    /**
     * Returns the CollectorRegistry used by the MetricsService.
     *
     * @return \Prometheus\CollectorRegistry The CollectorRegistry used by the MetricsService.
     */
    public function getMetrics(): CollectorRegistry
    {
        return $this->collectionRegistry;
    }

    /**
     * Retrieves a metric by its name. The app_ prefix is added to the name of the metric.
     * Example:
     * $metricsService->getMetricByName('app_http_requests_total');
     *
     * @param string $name The name of the metric to retrieve.
     * @return \Prometheus\MetricFamilySamples|null The metric with the specified name, or null if not found.
     */
    public function getMetricByName(string $name)
    {
        $metrics = $this->collectionRegistry->getMetricFamilySamples();
        foreach ($metrics as $metric) {
            if ($metric->getName() === $name) {
                return $metric;
            }
        }
        return null;
    }

    /**
     * Registers a new counter metric.
     * Example:
     * $metricsService->registerCounter('app_http_requests_total', 'Total HTTP requests', ['method', 'endpoint', 'status']);
     *
     * @param string $name The name of the counter.
     * @param string $help The help text of the counter.
     * @param array $labels The labels of the counter.
     * @return \Prometheus\Counter The registered counter.
     * @throws \RuntimeException If a metric with the same name already exists.
     */
    public function registerCounter(string $name, string $help, array $labels = []): \Prometheus\Counter
    {
        if ($this->getMetricByName($name) !== null) {
            throw new RuntimeException("A metric with this name already exists. '{$name}'.");
        }
        return $this->collectionRegistry->registerCounter($this->namespace, $name, $help, $labels);
    }

    /**
     * Registers a new histogram metric.
     * Example:
     * $metricsService->registerHistogram('app_http_request_duration_seconds', 'HTTP request duration in seconds', ['method', 'endpoint'], [0.1, 0.5, 1, 5, 10]);
     *
     * @param string $name The name of the histogram.
     * @param string $help The help text of the histogram.
     * @param array $labels The labels of the histogram.
     * @param array $buckets The buckets of the histogram.
     * @return \Prometheus\Histogram The registered histogram.
     */
    public function registerHistogram(string $name, string $help, array $labels = [], array $buckets = [0.1, 1, 5, 10]): \Prometheus\Histogram
    {
        return $this->collectionRegistry->registerHistogram($this->namespace, $name, $help, $labels, $buckets);
    }

    /**
     * Registers a new gauge metric.
     * Example:
     * $metricsService->registerGauge('app_active_jobs', 'Number of active jobs in the queue', ['queue']);
     *
     * @param string $name The name of the gauge.
     * @param string $help The help text of the gauge.
     * @param array $labels The labels of the gauge.
     * @return \Prometheus\Gauge The registered gauge.
     */
    public function registerGauge(string $name, string $help, array $labels = []): \Prometheus\Gauge
    {
        return $this->collectionRegistry->registerGauge($this->namespace, $name, $help, $labels);
    }

    /**
     * Sets a gauge metric to a specific value.
     * Example:
     * $metricsService->setGauge('app_active_jobs', 10, ['queue1']);
     *
     * @param string $name The name of the gauge.
     * @param float $value The value to set the gauge to.
     * @param array $labelValues The values of the labels for the gauge.
     */
    public function setGauge(string $name, float $value, array $labelValues = []): void
    {
        $gauge = $this->collectionRegistry->getGauge($this->namespace, $name);
        $gauge->set($value, $labelValues);
    }

    /**
     * Increments a counter metric by 1.
     * Example:
     * $metricsService->incrementCounter('app_http_requests_total', ['GET', '/api/v1/users', '200']);
     *
     * @param string $name The name of the counter.
     * @param array $labelValues The values of the labels for the counter.
     * @throws \RuntimeException If the counter could not be incremented.
     */
    public function incrementCounter(string $name, array $labelValues = []): void
    {
        try {
            $counter = $this->collectionRegistry->getCounter($this->namespace, $name);
            $counter->inc($labelValues);
        } catch (Exception $e) {
            throw new RuntimeException("The counter could not be incremented '{$name}': " . $e->getMessage());
        }
    }

    /**
     * Observes a value for a histogram metric.
     * Example:
     * $metricsService->observeHistogram('app_http_request_duration_seconds', 0.3, ['GET', '/api/v1/users']);
     *
     * @param string $name The name of the histogram.
     * @param float $value The value to observe.
     * @param array $labelValues The values of the labels for the histogram.
     */
    public function observeHistogram(string $name, float $value, array $labelValues = []): void
    {
        $histogram = $this->collectionRegistry->getHistogram($this->namespace, $name);
        $histogram->observe($value, $labelValues);
    }

    /**
     * Retrieves or increments a value stored in the cache and sets it to a Prometheus gauge.
     * Example: This is useful to monitor the number of active jobs in the queue.
     * $metricsService->cacheToGauge('app_active_jobs', 'app_active_jobs', 'Number of active jobs in the queue', ['queue'], 1);
     *
     * @param string $key The cache key.
     * @param string $metricName The Prometheus metric name.
     * @param string $help The help text for the gauge.
     * @param array $labels The labels for the gauge.
     * @param float $increment The value to increment.
     */
    public function cacheToGauge(string $key, string $metricName, string $help, array $labels = [], float $increment = 0): void
    {
        // Retrieve and update the cache
        $currentValue = Cache::increment($key, $increment);

        // Register the gauge if it doesn't exist
        if ($this->getMetricByName($metricName) === null) {
            $this->registerGauge($metricName, $help, $labels);
        }

        // Update the gauge with the cached value
        $this->setGauge($metricName, $currentValue, []);
    }

    /**
     * Renders the metrics in the Prometheus text format.
     * Example:
     * $metricsService->renderMetrics();
     *
     * @return string The rendered metrics.
     */
    public function renderMetrics()
    {
        $renderer = new RenderTextFormat();
        $metrics = $this->collectionRegistry->getMetricFamilySamples();
        return $renderer->render($metrics);
    }

    /**
     * Helper to register a default set of metrics for common Laravel use cases.
     */
    public function registerDefaultMetrics(): void
    {
        $this->registerCounter('http_requests_total', 'Total HTTP requests', ['method', 'endpoint', 'status']);
        $this->registerHistogram('http_request_duration_seconds', 'HTTP request duration in seconds', ['method', 'endpoint'], [0.1, 0.5, 1, 5, 10]);
        $this->registerGauge('active_jobs', 'Number of active jobs in the queue', ['queue']);
        $this->registerCounter('job_failures_total', 'Total number of failed jobs', ['queue']);
    }
}
