<?php

namespace ProcessMaker\Cache\Monitoring;

use ProcessMaker\Cache\CacheInterface;
use ProcessMaker\Cache\Monitoring\CacheMetricsInterface;
use ProcessMaker\Contracts\PrometheusMetricInterface;

/**
 * Decorator class that adds metrics tracking about cache operations
 * including hits, misses, write sizes, and timing information.
 */
class CacheMetricsDecorator implements CacheInterface
{
    protected CacheInterface $cache;

    protected CacheMetricsInterface $metrics;

    /**
     * Create a new cache metrics decorator instance
     *
     * @param CacheInterface|ScreenCacheInterface $cache The cache implementation to decorate
     * @param CacheMetricsInterface $metrics The metrics implementation to use
     */
    public function __construct(CacheInterface $cache, CacheMetricsInterface $metrics)
    {
        $this->cache = $cache;
        $this->metrics = $metrics;
    }

    /**
     * Create a cache key based on provided parameters
     *
     * @param array $params Key parameters
     * @return string Generated cache key
     */
    public function createKey(array $params): string
    {
        if ($this->cache instanceof CacheInterface) {
            return $this->cache->createKey($params);
        }

        throw new \RuntimeException('Underlying cache implementation does not support createKey method');
    }

    /**
     * Get a value from the cache
     *
     * Records timing and hit/miss metrics for the get operation
     *
     * @param string $key Cache key
     * @param mixed $default Default value if key not found
     * @return mixed Cached value or default
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $startTime = microtime(true);

        // First check if the key exists
        $exists = $this->cache->has($key);

        // Get the value
        $value = $this->cache->get($key, $default);

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Get extra labels for metrics
        $labels = [];
        if ($value instanceof PrometheusMetricInterface) {
            $labels['label'] = $value->getPrometheusMetricLabel();
        } else {
            $labels['label'] = $key;
        }
        // Record metrics based on key existence, not value comparison
        if ($exists) {
            $this->metrics->recordHit($key, $duration, $labels);
        } else {
            $this->metrics->recordMiss($key, $duration, $labels);
        }

        return $value;
    }

    /**
     * Store a value in the cache
     *
     * Records metrics about the size of stored data
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param null|int|\DateInterval $ttl Optional TTL
     * @return bool True if successful
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $result = $this->cache->set($key, $value, $ttl);

        // Get extra labels for metrics
        $labels = [];
        if ($value instanceof PrometheusMetricInterface) {
            $labels['label'] = $value->getPrometheusMetricLabel();
        } else {
            $labels['label'] = $key;
        }

        if ($result) {
            // Calculate approximate size in bytes
            $size = $this->calculateSize($value);
            $this->metrics->recordWrite($key, $size, $labels);
        }

        return $result;
    }

    /**
     * Delete a value from the cache
     *
     * @param string $key Cache key
     * @return bool True if successful
     */
    public function delete(string $key): bool
    {
        return $this->cache->delete($key);
    }

    /**
     * Clear all values from the cache
     *
     * @return bool True if successful
     */
    public function clear(): bool
    {
        return $this->cache->clear();
    }

    /**
     * Check if a key exists in the cache
     *
     * @param string $key Cache key
     * @return bool True if key exists
     */
    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    /**
     * Check if a key is missing from the cache
     *
     * @param string $key Cache key
     * @return bool True if key is missing
     */
    public function missing(string $key): bool
    {
        return $this->cache->missing($key);
    }

    /**
     * Invalidate cache for a specific screen
     *
     * @param int $screenId Screen ID
     * @return bool
     * @throws \RuntimeException If underlying cache doesn't support invalidate
     */
    public function invalidate($params): void
    {
        if (!$this->cache instanceof CacheInterface) {
            throw new \RuntimeException('Underlying cache implementation does not support invalidate method');
        }

        $this->cache->invalidate($params);
    }

    /**
     * Calculate the approximate size in bytes of a value
     *
     * @param mixed $value Value to calculate size for
     * @return int Size in bytes
     */
    protected function calculateSize(mixed $value): int
    {
        if (is_string($value)) {
            return strlen($value);
        }

        if (is_array($value) || is_object($value)) {
            return strlen(serialize($value));
        }

        if (is_int($value)) {
            return PHP_INT_SIZE;
        }

        if (is_float($value)) {
            return 8; // typical double size
        }

        if (is_bool($value)) {
            return 1;
        }

        return 0; // for null or other types
    }

    /**
     * Get a value from the cache or store it if it doesn't exist.
     *
     * @param string $key
     * @param callable $callback
     * @return mixed
     */
    public function getOrCache(string $key, callable $callback): mixed
    {
        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }

        $value = $callback();
        $this->cache->set($key, $value);

        return $value;
    }

    /**
     * Clear compiled assets from cache and record metrics
     *
     * This method clears compiled assets from the cache and records the operation
     * as a write with size 0 since we are removing content rather than adding it.
     * The execution time is measured but not currently used.
     */
    public function clearCompiledAssets(): void
    {
        $startTime = microtime(true);
        $this->cache->clearCompiledAssets();
        $timeTaken = microtime(true) - $startTime;
    }
}
