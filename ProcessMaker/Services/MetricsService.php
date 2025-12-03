<?php

namespace ProcessMaker\Services;

use Exception;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;
use ProcessMaker\Facades\Metrics;
use ProcessMaker\Multitenancy\Tenant;
use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\Histogram;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\Redis as PrometheusRedis;
use Redis;
use RuntimeException;

class MetricsService
{
    /**
     * The CollectorRegistry instance used by the MetricsService.
     *
     * @var CollectorRegistry
     */
    private $collectionRegistry;

    /**
     * The namespace used by the MetricsService.
     *
     * @var string
     */
    private $namespace;

    /**
     * Initializes the MetricsService with a CollectorRegistry using the provided storage adapter.
     *
     * @param mixed $adapter The storage adapter to use (e.g., Redis).
     */
    public function __construct(private $adapter = null)
    {
        $this->namespace = config('app.prometheus_namespace', 'app');
        try {
            // Set up Redis as the adapter if none is provided
            if ($adapter === null) {
                $redis = app('redis')->client();
                $adapter = PrometheusRedis::fromExistingConnection($redis);
                if (app()->has(Tenant::BOOTSTRAPPED_TENANT)) {
                    $tenantInfo = app(Tenant::BOOTSTRAPPED_TENANT);
                    $adapter->setPrefix('tenant_' . $tenantInfo['id'] . ':PROMETHEUS_');
                }
            }
            $this->collectionRegistry = new CollectorRegistry($adapter);
        } catch (Exception $e) {
            throw new RuntimeException('Error initializing the metrics adapter: ' . $e->getMessage());
        }
    }

    /**
     * Get the collection registry.
     *
     * @return CollectorRegistry The collection registry instance.
     */
    public function getCollectionRegistry(): CollectorRegistry
    {
        return $this->collectionRegistry;
    }

    /**
     * Registers or retrieves a counter metric.
     *
     * @param string $name The name of the counter.
     * @param string|null $help The help text of the counter.
     * @param array $labels The labels of the counter.
     * @return Counter The registered or retrieved counter.
     */
    public function counter(string $name, string|null $help = null, array $labels = []): Counter
    {
        $help = $help ?? $name;

        return $this->collectionRegistry->getOrRegisterCounter(
            $this->namespace,
            $name,
            $help,
            $labels
        );
    }

    /**
     * Registers or retrieves a gauge metric.
     *
     * @param string $name The name of the gauge.
     * @param string|null $help The help text of the gauge.
     * @param array $labels The labels of the gauge.
     * @return Gauge The registered or retrieved gauge.
     */
    public function gauge(string $name, string|null $help = null, array $labels = []): Gauge
    {
        $help = $help ?? $name;

        return $this->collectionRegistry->getOrRegisterGauge(
            $this->namespace,
            $name,
            $help,
            $labels
        );
    }

    /**
     * Registers or retrieves a histogram metric.
     *
     * @param string $name The name of the histogram.
     * @param string|null $help The help text of the histogram.
     * @param array $labels The labels of the histogram.
     * @param array $buckets The buckets of the histogram.
     * @return Histogram The registered or retrieved histogram.
     */
    public function histogram(string $name, string $help = null, array $labels = [], array $buckets = [0.1, 1, 5, 10]): Histogram
    {
        $help = $help ?? $name;

        return $this->collectionRegistry->getOrRegisterHistogram(
            $this->namespace,
            $name,
            $help,
            $labels,
            $buckets
        );
    }

    /**
     * Sets a gauge metric to a specific value.
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
     * Renders the metrics in the Prometheus text format.
     *
     * @return string The rendered metrics.
     */
    public function renderMetrics(): string
    {
        $renderer = new RenderTextFormat();
        $metrics = $this->collectionRegistry->getMetricFamilySamples();

        return $renderer->render($metrics);
    }

    /**
     * Increments a counter metric by 1.
     *
     * @param string $name The name of the counter.
     * @param string|null $help The help text of the counter.
     * @param array $labels The labels of the counter.
     *
     * @return void
     */
    public function counterInc(string $name, string $help = null, array $labels = []): void
    {
        // Add system labels
        $labels = $this->addSystemLabels($labels);
        $labelKeys = array_keys($labels);
        Metrics::counter($name, $help, $labelKeys)->inc($labels);
    }

    /**
     * Histogram observation.
     *
     * @param string $name The name of the histogram.
     * @param string|null $help The help text of the histogram.
     * @param array $labels The labels of the histogram.
     * @param array $buckets The buckets of the histogram.
     * @param float $executionTime The execution
     *
     * @return void
     */
    public function histogramObserve(string $name, string $help = null, array $labels = [], array $buckets = [0.1, 1, 5, 10], float $executionTime = 0): void
    {
        // Add system labels
        $labels = $this->addSystemLabels($labels);
        $labelKeys = array_keys($labels);
        Metrics::histogram(
            $name,
            $help,
            $labelKeys,
            $buckets
        )->observe(
            $executionTime,
            $labels
        );
    }

    /**
     * Add system labels to the provided labels.
     *
     * @param array $labels The labels to add system labels to.
     *
     * @return array The keys of the labels.
     */
    public function addSystemLabels(array $labels)
    {
        // Add system labels
        $labels['app_version'] = $this->getApplicationVersion();
        $labels['app_name'] = config('app.name');
        if (config('app.prometheus_custom_label')) {
            $labels['app_custom_label'] = config('app.prometheus_custom_label');
        }

        return $labels;
    }

    public function clearMetrics(): void
    {
        $this->collectionRegistry->wipeStorage();
    }

    /**
     * Gets the version of the application.
     *
     * @return string The version of the application.
     */
    private function getApplicationVersion()
    {
        $root = base_path('composer.json');
        $composer_json_path = json_decode(file_get_contents($root));

        return $composer_json_path->version ?? '4.0.0';
    }

    /**
     * These are collected every time the /metrics route is accessed.
     *
     * @return void
     */
    public function collectQueueMetrics(): void
    {
        $metricsRepository = app(MetricsRepository::class);
        $jobsRepository = app(JobRepository::class);
        $workloadRepository = app(WorkloadRepository::class);

        $this->gauge('horizon_jobs_per_minute', 'Jobs processed per minute')->set($metricsRepository->jobsProcessedPerMinute());
        $this->gauge('horizon_failed_jobs_per_hour', 'Failed jobs per hour')->set($jobsRepository->countRecentlyFailed());

        foreach ($workloadRepository->get() as $workload) {
            $name = $workload['name'];
            foreach (['length', 'wait', 'processes'] as $type) {
                $this->gauge(
                    'horizon_workload_' . $name . '_' . $type,
                    'Workload ' . $name . ' ' . $type
                )->set($workload[$type]);
            }
        }
    }
}
