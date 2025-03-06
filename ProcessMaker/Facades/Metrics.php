<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;
use ProcessMaker\Services\MetricsService;

/**
 * @method static \Prometheus\Counter counter(string $name, string $help = null, array $labels = [])
 * @method static \Prometheus\Gauge gauge(string $name, string $help = null, array $labels = [])
 * @method static \Prometheus\Histogram histogram(string $name, string $help = null, array $labels = [], array $buckets = [0.1, 1, 5, 10])
 * @method static void setGauge(string $name, float $value, array $labelValues = [])
 * @method static string renderMetrics()
 * @method static \Prometheus\CollectorRegistry getCollectionRegistry()
 * @method static void counterInc(string $name, string $help = null, array $labels = [])
 * @method static void histogramObserve(string $name, string $help = null, array $labels = [], array $buckets = [0.1, 1, 5, 10], float $executionTime)
 * @method static void clearMetrics()
 */
class Metrics extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return MetricsService::class;
    }
}
