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
     *
     * @return array An array of cache keys that match the pattern.
     */
    public function getKeysByPattern(string $pattern): array
    {
        if (!in_array($this->connection, self::AVAILABLE_CONNECTIONS)) {
            throw new Exception('`getKeysByPattern` method only supports Redis connections.');
        }

        try {
            // Get all keys
            $keys = Redis::connection($this->connection)->keys($this->prefix . '*');
            // Filter keys by pattern
            return array_filter($keys, fn ($key) => preg_match('/' . $pattern . '/', $key));
        } catch (Exception $e) {
            Log::info('CacheABC' . $e->getMessage());
        }

        return [];
    }
}
