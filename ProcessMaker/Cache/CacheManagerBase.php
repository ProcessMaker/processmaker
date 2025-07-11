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
     * Check if a connection is available.
     *
     * @param string $connection The connection to check.
     *
     * @return bool True if the connection is available, false otherwise.
     */
    private function checkAvailableConnections(string $connection): bool
    {
        return in_array($connection, self::AVAILABLE_CONNECTIONS);
    }

    /**
     * Get the prefix for a specific connection.
     *
     * @param string $connection The connection to get the prefix for.
     *
     * @return string The prefix for the connection.
     */
    private function getPrefix(string $connection): string
    {
        $prefix = config('cache.prefix');

        if ($connection === 'cache_settings') {
            $prefix = config('cache.stores.' . $connection . '.prefix');
        }

        return $prefix;
    }

    /**
     * Retrieve an array of cache keys that match a specific pattern.
     *
     * @param string $pattern The pattern to match.
     * @param string|null $connection The cache connection to use.
     *
     * @return array An array of cache keys that match the pattern.
     */
    public function getKeysByPattern(string $pattern, ?string $connection = null, ?string $prefix = null): array
    {
        if (!$connection) {
            $connection = config('cache.default');
        }

        if (!$this->checkAvailableConnections($connection)) {
            throw new CacheManagerException('`getKeysByPattern` method only supports Redis connections.');
        }

        if (!$prefix) {
            $prefix = $this->getPrefix($connection);
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

    /**
     * Check if a key exists in the cache.
     *
     * @param string $key The key to check.
     * @param string|null $connection The cache connection to use.
     * @param string|null $prefix The cache prefix to use.
     *
     * @return bool True if the key exists, false otherwise.
     */
    public function keyExists(string $key, ?string $connection = null, ?string $prefix = null): bool
    {
        if (!$connection) {
            $connection = config('cache.stores.cache_settings.connection');
        }

        if (!$this->checkAvailableConnections($connection)) {
            return false;
        }

        if (!$prefix) {
            $prefix = $this->getPrefix($connection);
        }

        try {
            return Redis::connection($connection)->exists($prefix . $key);
        } catch (Exception $e) {
            Log::info('CacheManagerBase: ' . $e->getMessage());
        }

        return false;
    }
}
