<?php

namespace ProcessMaker\Cache\Monitoring;

use Illuminate\Support\Facades\Redis;

class RedisMetricsManager implements CacheMetricsInterface
{
    protected const METRICS_PREFIX = 'cache:metrics:';

    protected const HITS_KEY = 'hits';

    protected const MISSES_KEY = 'misses';

    protected const HIT_TIMES_KEY = 'hit_times';

    protected const MISS_TIMES_KEY = 'miss_times';

    protected const MEMORY_KEY = 'memory';

    protected const LAST_WRITE_KEY = 'last_write';

    /**
     * Record a cache hit
     *
     * @param string $key Cache key
     * @param float $microtime Time taken in microseconds
     */
    public function recordHit(string $key, $microtime, array $labels = []): void
    {
        $baseKey = self::METRICS_PREFIX . $key;
        Redis::pipeline(function ($pipe) use ($baseKey, $microtime) {
            $pipe->hincrby($baseKey, self::HITS_KEY, 1);
            $pipe->rpush($baseKey . ':' . self::HIT_TIMES_KEY, $microtime);
            $pipe->ltrim($baseKey . ':' . self::HIT_TIMES_KEY, -100, -1);
        });
    }

    /**
     * Record a cache miss
     *
     * @param string $key Cache key
     * @param float $microtime Time taken in microseconds
     */
    public function recordMiss(string $key, $microtime, array $labels = []): void
    {
        $baseKey = self::METRICS_PREFIX . $key;
        Redis::pipeline(function ($pipe) use ($baseKey, $microtime) {
            $pipe->hincrby($baseKey, self::MISSES_KEY, 1);
            $pipe->rpush($baseKey . ':' . self::MISS_TIMES_KEY, $microtime);
            $pipe->ltrim($baseKey . ':' . self::MISS_TIMES_KEY, -100, -1);
        });
    }

    /**
     * Record a cache write operation
     *
     * @param string $key Cache key
     * @param int $size Size in bytes
     */
    public function recordWrite(string $key, int $size, array $labels = []): void
    {
        $baseKey = self::METRICS_PREFIX . $key;
        Redis::pipeline(function ($pipe) use ($baseKey, $size) {
            $pipe->hset($baseKey, self::MEMORY_KEY, $size);
            $pipe->hset($baseKey, self::LAST_WRITE_KEY, microtime(true));
        });
    }

    /**
     * Get hit rate for a specific key
     *
     * @param string $key Cache key
     * @return float Hit rate percentage (0-1)
     */
    public function getHitRate(string $key): float
    {
        $baseKey = self::METRICS_PREFIX . $key;
        $hits = (int) Redis::hget($baseKey, self::HITS_KEY) ?: 0;
        $misses = (int) Redis::hget($baseKey, self::MISSES_KEY) ?: 0;
        $total = $hits + $misses;

        return $total > 0 ? $hits / $total : 0;
    }

    /**
     * Get miss rate for a specific key
     *
     * @param string $key Cache key
     * @return float Miss rate percentage (0-1)
     */
    public function getMissRate(string $key): float
    {
        $baseKey = self::METRICS_PREFIX . $key;
        $hits = (int) Redis::hget($baseKey, self::HITS_KEY) ?: 0;
        $misses = (int) Redis::hget($baseKey, self::MISSES_KEY) ?: 0;
        $total = $hits + $misses;

        return $total > 0 ? $misses / $total : 0;
    }

    /**
     * Get average hit time for a specific key
     *
     * @param string $key Cache key
     * @return float Average hit time in seconds
     */
    public function getHitAvgTime(string $key): float
    {
        $times = Redis::lrange(self::METRICS_PREFIX . $key . ':' . self::HIT_TIMES_KEY, 0, -1);
        if (empty($times)) {
            return 0;
        }

        return array_sum(array_map('floatval', $times)) / count($times);
    }

    /**
     * Get average miss time for a specific key
     *
     * @param string $key Cache key
     * @return float Average miss time in seconds
     */
    public function getMissAvgTime(string $key): float
    {
        $times = Redis::lrange(self::METRICS_PREFIX . $key . ':' . self::MISS_TIMES_KEY, 0, -1);
        if (empty($times)) {
            return 0;
        }

        return array_sum(array_map('floatval', $times)) / count($times);
    }

    /**
     * Get top accessed keys
     *
     * @param int $count Number of keys to return
     * @return array Top keys with their metrics
     */
    public function getTopKeys(int $count = 5): array
    {
        $keys = Redis::keys(self::METRICS_PREFIX . '*');
        $metrics = [];

        foreach ($keys as $redisKey) {
            if (str_contains($redisKey, ':' . self::HIT_TIMES_KEY) ||
                str_contains($redisKey, ':' . self::MISS_TIMES_KEY)) {
                continue;
            }

            $key = str_replace(self::METRICS_PREFIX, '', $redisKey);
            $hits = (int) Redis::hget($redisKey, self::HITS_KEY) ?: 0;
            $misses = (int) Redis::hget($redisKey, self::MISSES_KEY) ?: 0;
            $total = $hits + $misses;

            if ($total > 0) {
                $metrics[$key] = [
                    'key' => $key,
                    'hits' => $hits,
                    'misses' => $misses,
                    'total_accesses' => $total,
                    'hit_ratio' => $hits / $total,
                    'miss_ratio' => $misses / $total,
                    'avg_hit_time' => $this->getHitAvgTime($key),
                    'avg_miss_time' => $this->getMissAvgTime($key),
                    'memory_usage' => $this->getMemoryUsage($key)['current_size'],
                    'last_write' => Redis::hget($redisKey, self::LAST_WRITE_KEY),
                ];
            }
        }

        uasort($metrics, fn ($a, $b) => $b['total_accesses'] <=> $a['total_accesses']);

        return array_slice($metrics, 0, $count, true);
    }

    /**
     * Get memory usage for a specific key
     *
     * @param string $key Cache key
     * @return array Memory usage statistics
     */
    public function getMemoryUsage(string $key): array
    {
        $baseKey = self::METRICS_PREFIX . $key;
        $currentSize = (int) Redis::hget($baseKey, self::MEMORY_KEY) ?: 0;
        $lastWrite = Redis::hget($baseKey, self::LAST_WRITE_KEY);

        return [
            'current_size' => $currentSize,
            'last_write' => $lastWrite ? (float) $lastWrite : null,
        ];
    }

    /**
     * Reset all metrics
     */
    public function resetMetrics(): void
    {
        $keys = Redis::keys(self::METRICS_PREFIX . '*');
        if (!empty($keys)) {
            Redis::del(...$keys);
        }
    }

    /**
     * Get summary of all metrics
     *
     * @return array Summary statistics
     */
    public function getSummary(): array
    {
        $keys = Redis::keys(self::METRICS_PREFIX . '*');
        $metrics = [];
        $totalHits = 0;
        $totalMisses = 0;
        $totalMemory = 0;
        $totalHitTime = 0;
        $totalMissTime = 0;
        $keyCount = 0;

        foreach ($keys as $redisKey) {
            if (str_contains($redisKey, ':' . self::HIT_TIMES_KEY) ||
                str_contains($redisKey, ':' . self::MISS_TIMES_KEY)) {
                continue;
            }

            $key = str_replace(self::METRICS_PREFIX, '', $redisKey);
            $hits = (int) Redis::hget($redisKey, self::HITS_KEY) ?: 0;
            $misses = (int) Redis::hget($redisKey, self::MISSES_KEY) ?: 0;
            $memory = (int) Redis::hget($redisKey, self::MEMORY_KEY) ?: 0;

            $totalHits += $hits;
            $totalMisses += $misses;
            $totalMemory += $memory;
            $totalHitTime += $this->getHitAvgTime($key);
            $totalMissTime += $this->getMissAvgTime($key);

            // Total represents the total number of cache access attempts (hits + misses)
            // We need this sum to calculate hit_ratio and miss_ratio percentages
            // Example: If hits=8 and misses=2, total=10, so hit_ratio=8/10=0.8 (80%) and miss_ratio=2/10=0.2 (20%)
            $total = $hits + $misses;
            $metrics[$key] = [
                'hits' => $hits,
                'misses' => $misses,
                'hit_ratio' => $total > 0 ? $hits / $total : 0,
                'miss_ratio' => $total > 0 ? $misses / $total : 0,
                'avg_hit_time' => $this->getHitAvgTime($key),
                'avg_miss_time' => $this->getMissAvgTime($key),
                'memory_usage' => $memory,
            ];

            $keyCount++;
        }

        $total = $totalHits + $totalMisses;

        return [
            'keys' => $metrics,
            'overall_hit_ratio' => $total > 0 ? $totalHits / $total : 0,
            'overall_miss_ratio' => $total > 0 ? $totalMisses / $total : 0,
            'avg_hit_time' => $keyCount > 0 ? $totalHitTime / $keyCount : 0,
            'avg_miss_time' => $keyCount > 0 ? $totalMissTime / $keyCount : 0,
            'total_memory_usage' => $totalMemory,
            'total_keys' => $keyCount,
        ];
    }
}
