<?php

namespace ProcessMaker\Cache\Monitoring;

use Illuminate\Support\Facades\Redis;
use ProcessMaker\Facades\Metrics;
use Prometheus\CollectorRegistry;

class PrometheusMetricsManager implements CacheMetricsInterface
{
    /**
     * @var Metrics
     */
    protected $metrics;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * PrometheusMetricsManager constructor.
     *
     * @param string $namespace
     */
    public function __construct(string $namespace = 'cache')
    {
        $this->metrics = Metrics::getFacadeRoot();
        $this->namespace = $namespace;
    }

    /**
     * Record a cache hit
     *
     * @param string $key Cache key
     * @param float $microtime Time taken in microseconds
     */
    public function recordHit(string $key, $microtime): void
    {
        $sanitizedKey = $this->sanitizeKey($key);
        $cacheType = $this->getCacheType($key);

        // Record total hits by cache type
        $this->metrics->counter(
            'cache_hits_total',
            'Total number of cache hits',
            ['cache_type', 'cache_key']
        )->inc(['cache_type' => $cacheType, 'cache_key' => $sanitizedKey]);

        // Record hit timing
        $this->metrics->histogram(
            'cache_hit_duration_seconds',
            'Time taken for cache hits',
            ['cache_type', 'cache_key'],
            [0.001, 0.005, 0.01, 0.05, 0.1, 0.5]
        )->observe(
            $microtime / 1000000, // Convert microseconds to seconds
            ['cache_type' => $cacheType, 'cache_key' => $sanitizedKey]
        );
    }

    /**
     * Record a cache miss
     *
     * @param string $key Cache key
     * @param float $microtime Time taken in microseconds
     */
    public function recordMiss(string $key, $microtime): void
    {
        $sanitizedKey = $this->sanitizeKey($key);
        $cacheType = $this->getCacheType($key);

        // Record total misses by cache type
        $this->metrics->counter(
            'cache_misses_total',
            'Total number of cache misses',
            ['cache_type', 'cache_key']
        )->inc(['cache_type' => $cacheType, 'cache_key' => $sanitizedKey]);

        // Record miss timing
        $this->metrics->histogram(
            'cache_miss_duration_seconds',
            'Time taken for cache misses',
            ['cache_type', 'cache_key'],
            [0.01, 0.05, 0.1, 0.5, 1.0]
        )->observe(
            $microtime / 1000000, // Convert microseconds to seconds
            ['cache_type' => $cacheType, 'cache_key' => $sanitizedKey]
        );
    }

    /**
     * Record a cache write operation
     *
     * @param string $key Cache key
     * @param int $size Size in bytes
     */
    public function recordWrite(string $key, int $size): void
    {
        $sanitizedKey = $this->sanitizeKey($key);
        $cacheType = $this->getCacheType($key);

        // Record memory usage by cache type and key
        $this->metrics->gauge(
            'cache_memory_bytes',
            'Memory usage in bytes',
            ['cache_type', 'cache_key']
        )->set($size, ['cache_type' => $cacheType, 'cache_key' => $sanitizedKey]);

        // Record total memory by cache type
        $this->metrics->gauge(
            'cache_type_memory_bytes',
            'Total memory usage by cache type',
            ['cache_type']
        )->inc($size, ['cache_type' => $cacheType]);
    }

    /**
     * Sanitize a cache key to be used as a Prometheus label
     *
     * @param string $key Cache key
     * @return string Sanitized cache key
     */
    protected function sanitizeKey(string $key): string
    {
        return str_replace([':', '/', ' '], '_', $key);
    }

    /**
     * Determine cache type from key
     *
     * @param string $key Cache key
     * @return string Cache type (screens|settings)
     */
    protected function getCacheType(string $key): string
    {
        if (strpos($key, '/screens') === 0 || strpos($key, 'screen') === 0) {
            return 'screens';
        }
        if (strpos($key, '/settings') === 0) {
            return 'settings';
        }

        return 'unknown';
    }
}
