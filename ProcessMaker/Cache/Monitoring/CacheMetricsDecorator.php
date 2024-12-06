<?php

namespace ProcessMaker\Cache\Monitoring;

use ProcessMaker\Cache\CacheInterface;
use ProcessMaker\Cache\Monitoring\CacheMetricsInterface;
use ProcessMaker\Cache\Screens\ScreenCacheInterface;

/**
 * Decorator class that adds metrics tracking about cache operations
 * including hits, misses, write sizes, and timing information.
 */
class CacheMetricsDecorator implements CacheInterface, ScreenCacheInterface
{
    protected CacheInterface|ScreenCacheInterface $cache;

    protected CacheMetricsInterface $metrics;

    /**
     * Create a new cache metrics decorator instance
     *
     * @param CacheInterface|ScreenCacheInterface $cache The cache implementation to decorate
     * @param CacheMetricsInterface $metrics The metrics implementation to use
     */
    public function __construct(CacheInterface|ScreenCacheInterface $cache, CacheMetricsInterface $metrics)
    {
        $this->cache = $cache;
        $this->metrics = $metrics;
    }

    /**
     * Create a cache key for screen data
     *
     * @param int $processId Process ID
     * @param int $processVersionId Process version ID
     * @param string $language Language code
     * @param int $screenId Screen ID
     * @param int $screenVersionId Screen version ID
     * @return string Generated cache key
     * @throws \RuntimeException If underlying cache doesn't support createKey
     */
    public function createKey(int $processId, int $processVersionId, string $language, int $screenId, int $screenVersionId): string
    {
        if ($this->cache instanceof ScreenCacheInterface) {
            return $this->cache->createKey($processId, $processVersionId, $language, $screenId, $screenVersionId);
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
        $value = $this->cache->get($key, $default);
        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        if ($value === $default) {
            $this->metrics->recordMiss($key, $duration);
        } else {
            $this->metrics->recordHit($key, $duration);
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

        if ($result) {
            // Calculate approximate size in bytes
            $size = $this->calculateSize($value);
            $this->metrics->recordWrite($key, $size);
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
}
