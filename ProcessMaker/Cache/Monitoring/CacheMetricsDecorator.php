<?php

namespace ProcessMaker\Cache\Monitoring;

use ProcessMaker\Cache\CacheInterface;

class CacheMetricsDecorator implements CacheInterface
{
    protected CacheInterface $cache;

    protected CacheMetricsInterface $metrics;

    public function __construct(CacheInterface $cache, CacheMetricsInterface $metrics)
    {
        $this->cache = $cache;
        $this->metrics = $metrics;
    }

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

    public function delete(string $key): bool
    {
        return $this->cache->delete($key);
    }

    public function clear(): bool
    {
        return $this->cache->clear();
    }

    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    public function missing(string $key): bool
    {
        return $this->cache->missing($key);
    }

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
