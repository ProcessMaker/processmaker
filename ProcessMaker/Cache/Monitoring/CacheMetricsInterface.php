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
}
