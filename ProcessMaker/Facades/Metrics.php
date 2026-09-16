<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;
use ProcessMaker\Services\MetricsService;

/**
 * @method static \Prometheus\Counter counter(string $name, string|null $help = null, array $labels = [])
 * @method static \Prometheus\Gauge gauge(string $name, string|null $help = null, array $labels = [])
 * @method static \Prometheus\Histogram histogram(string $name, string|null $help = null, array $labels = [], array $buckets = [])
 * @method static void setGauge(string $name, float $value, array $labelValues = [])
 * @method static string renderMetrics()
 * @method static \Prometheus\CollectorRegistry getCollectionRegistry()
 * @method static void counterInc(string $name, string|null $help = null, array $labels = [])
 * @method static void histogramObserve(string $name, string|null $help = null, array $labels = [], array $buckets = [], float $executionTime = 0)
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
