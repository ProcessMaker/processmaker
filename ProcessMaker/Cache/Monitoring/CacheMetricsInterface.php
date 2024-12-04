<?php

namespace ProcessMaker\Cache\Monitoring;

/**
 * Interface for monitoring cache metrics and performance
 */
interface CacheMetricsInterface
{
    /**
     * Record a cache hit event
     *
     * @param string $key Cache key that was accessed
     * @param float $microtime Time taken for the operation in microseconds
     */
    public function recordHit(string $key, $microtime): void;

    /**
     * Record a cache miss event
     *
     * @param string $key Cache key that was accessed
     * @param float $microtime Time taken for the operation in microseconds
     */
    public function recordMiss(string $key, $microtime): void;

    /**
     * Record a cache write operation
     *
     * @param string $key Cache key that was written
     * @param int $size Size of the cached data in bytes
     */
    public function recordWrite(string $key, int $size): void;

    /**
     * Get the hit rate for a specific cache key
     *
     * @param string $key Cache key to check
     * @return float Hit rate as a percentage between 0 and 1
     */
    public function getHitRate(string $key): float;

    /**
     * Get the miss rate for a specific cache key
     *
     * @param string $key Cache key to check
     * @return float Miss rate as a percentage between 0 and 1
     */
    public function getMissRate(string $key): float;

    /**
     * Get the average time taken for cache hits
     *
     * @param string $key Cache key to analyze
     * @return float Average time in microseconds
     */
    public function getHitAvgTime(string $key): float;

    /**
     * Get the average time taken for cache misses
     *
     * @param string $key Cache key to analyze
     * @return float Average time in microseconds
     */
    public function getMissAvgTime(string $key): float;

    /**
     * Get the most frequently accessed cache keys
     *
     * @param int $count Number of top keys to return
     * @return array Array of top keys with their access counts
     */
    public function getTopKeys(int $count = 5): array;

    /**
     * Get memory usage statistics for a cache key
     *
     * @param string $key Cache key to analyze
     * @return array Array containing memory usage details
     */
    public function getMemoryUsage(string $key): array;

    /**
     * Reset all metrics data
     */
    public function resetMetrics(): void;

    /**
     * Get a summary of all cache metrics
     *
     * @return array Array containing overall cache statistics
     */
    public function getSummary(): array;
}
