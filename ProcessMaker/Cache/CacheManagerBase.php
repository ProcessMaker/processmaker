<?php

namespace ProcessMaker\Cache;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

abstract class CacheManagerBase
{
    /**
     * The cache connection.
     *
     * @var string
     */
    protected string $connection;

    /**
     * The cache prefix.
     *
     * @var string
     */
    protected string $prefix;

    /**
     * The available cache connections.
     *
     * @var array
     */
    protected const AVAILABLE_CONNECTIONS = ['redis', 'cache_settings'];

    public function __construct()
    {
        $this->connection = config('cache.default');
        $this->prefix = config('cache.prefix');
    }

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
        if ($connection) {
            $this->connection = $connection;
        }

        if ($prefix) {
            $this->prefix = $prefix;
        }

        if (!in_array($this->connection, self::AVAILABLE_CONNECTIONS)) {
            throw new CacheManagerException('`getKeysByPattern` method only supports Redis connections.');
        }

        dump('getKeysByPattern prefix => ' . $this->connection . ' - ' . $this->prefix);

        try {
            // Get all keys
            $keys = Redis::connection($this->connection)->keys($this->prefix . '*');
            dump('getKeysByPattern keys => ' . json_encode($keys));
            // Filter keys by pattern
            return array_filter($keys, fn ($key) => preg_match('/' . $pattern . '/', $key));
        } catch (Exception $e) {
            dump('getKeysByPattern => ' . $e->getMessage());
            Log::info('CacheManagerBase: ' . $e->getMessage());
        }

        return [];
    }
}
