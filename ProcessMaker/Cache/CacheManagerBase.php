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
            // Filter keys by pattern
            return array_filter($keys, fn ($key) => preg_match('/' . $pattern . '/', $key));
        } catch (Exception $e) {
            Log::info('CacheManagerBase: ' . $e->getMessage());
        }

        return [];
    }
}
