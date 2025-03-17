<?php

namespace ProcessMaker\Cache;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

abstract class CacheManagerBase
{
    /**
     * The available cache connections.
     *
     * @var array
     */
    protected const AVAILABLE_CONNECTIONS = ['redis', 'cache_settings'];

    /**
     * Retrieve an array of cache keys that match a specific pattern.
     *
     * @param string $pattern The pattern to match.
     * @param string|null $connection The cache connection to use.
     *
     * @return array An array of cache keys that match the pattern.
     */
    public function getKeysByPattern(string $pattern, string $connection = null, string $prefix = null): array
    {
        if (!$connection) {
            $connection = config('cache.default');
        }

        if (!$prefix) {
            $prefix = config('cache.prefix');
        }

        if (!in_array($connection, self::AVAILABLE_CONNECTIONS)) {
            throw new CacheManagerException('`getKeysByPattern` method only supports Redis connections.');
        }

        try {
            // Get all keys
            $keys = Redis::connection($connection)->keys($prefix . '*');
            // Get the Redis prefix
            $redisPrefix = config('database.redis.options.prefix');

            // Filter keys by pattern and remove the REDIS_PREFIX
            $keys = array_map(function ($key) use ($redisPrefix) {
                return str_replace($redisPrefix, '', $key);
            }, preg_grep('/' . $pattern . '/', $keys));

            return $keys;
        } catch (Exception $e) {
            Log::info('CacheManagerBase: ' . $e->getMessage());
        }

        return [];
    }
}
