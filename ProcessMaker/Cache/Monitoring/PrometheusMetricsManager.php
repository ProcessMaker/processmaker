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
    public function recordHit(string $key, $microtime, array $labels = []): void
    {
        $sanitizedKey = $this->sanitizeKey($key);
        $labelKeys = array_keys($labels);

        $this->metrics->counter(
            'cache_hits_total',
            'Total number of cache hits',
            ['cache_key', ...$labelKeys]
        )->inc(['cache_key' => $sanitizedKey, ...$labels]);
        // record the last write timestamp
        $this->metrics->gauge(
            'cache_last_write_timestamp',
            'Last write timestamp',
            ['cache_key', ...$labelKeys]
        )->set($microtime, ['cache_key' => $sanitizedKey, ...$labels]);
    }

    /**
     * Record a cache miss
     *
     * @param string $key Cache key
     * @param float $microtime Time taken in microseconds
     */
    public function recordMiss(string $key, $microtime, array $labels = []): void
    {
        $sanitizedKey = $this->sanitizeKey($key);
        $labelKeys = array_keys($labels);

        $this->metrics->counter(
            'cache_misses_total',
            'Total number of cache misses',
            ['cache_key', ...$labelKeys]
        )->inc(['cache_key' => $sanitizedKey, ...$labels]);

        // record the last write timestamp
        $this->metrics->gauge(
            'cache_last_write_timestamp',
            'Last write timestamp',
            ['cache_key', ...$labelKeys]
        )->set($microtime, ['cache_key' => $sanitizedKey, ...$labels]);
    }

    /**
     * Record a cache write operation
     *
     * @param string $key Cache key
     * @param int $size Size in bytes
     */
    public function recordWrite(string $key, int $size, array $labels = []): void
    {
        $sanitizedKey = $this->sanitizeKey($key);
        $labelKeys = array_keys($labels);

        $this->metrics->gauge(
            'cache_memory_bytes',
            'Memory usage in bytes',
            ['cache_key', ...$labelKeys]
        )->set($size, ['cache_key' => $sanitizedKey, ...$labels]);
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
}
