<?php

namespace ProcessMaker\Cache\Settings;

use Illuminate\Cache\CacheManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use ProcessMaker\Cache\CacheInterface;

class SettingCacheManager implements CacheInterface
{
    const DEFAULT_CACHE_DRIVER = 'cache_settings';

    protected CacheManager $cacheManager;

    public function __construct(CacheManager $cacheManager)
    {
        $driver = $this->determineCacheDriver();

        $this->cacheManager = $cacheManager;
        $this->cacheManager->store($driver);
    }

    /**
     * Determine the cache driver to use.
     *
     * @return string
     */
    private function determineCacheDriver(): string
    {
        $defaultCache = config('cache.default');
        if (in_array($defaultCache, ['redis', 'cache_settings'])) {
            return self::DEFAULT_CACHE_DRIVER;
        }
        return $defaultCache;
    }

    /**
     * Dynamically pass method calls to the cache manager.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, $arguments): mixed
    {
        return $this->cacheManager->$method(...$arguments);
    }

    /**
     * Get a value from the settings cache.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cacheManager->get($key, $default);
    }

    /**
     * Get a value from the settings cache, or store the value from the callback if the key exists.
     *
     * @param string $key
     * @param callable $callback
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function getOrCache(string $key, callable $callback): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        try {
            $value = $callback();

            if ($value === null) {
                throw new \InvalidArgumentException('The key does not exist.');
            }
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('The key does not exist.');
        }

        $this->set($key, $value);

        return $value;
    }

    /**
     * Store a value in the settings cache.
     *
     * @param string $key
     * @param mixed $value
     * @param null|int|\DateInterval $ttl
     *
     * @return bool
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        return $this->cacheManager->put($key, $value, $ttl);
    }

    /**
     * Delete a value from the settings cache.
     *
     * @param string $key
     *
     * @return bool
     */
    public function delete(string $key): bool
    {
        return $this->cacheManager->forget($key);
    }

    /**
     * Clear the settings cache.
     *
     * @return bool
     */
    public function clear(): bool
    {
        return $this->cacheManager->clear();
    }

    /**
     * Remove items from the settings cache by a given pattern.
     *
     * @param string $pattern
     *
     * @throws \Exception
     * @return void
     */
    public function clearBy(string $pattern): void
    {
        $defaultDriver = $this->cacheManager->getDefaultDriver();

        if ($defaultDriver !== 'cache_settings') {
            throw new SettingCacheException('The cache driver must be Redis.');
        }

        try {
            // get the connection name from the cache manager
            $connection = $this->cacheManager->connection()->getName();
            // Get all keys
            $keys = Redis::connection($connection)->keys($this->cacheManager->getPrefix() . '*');
            // Filter keys by pattern
            $matchedKeys = array_filter($keys, fn($key) => preg_match('/' . $pattern . '/', $key));

            if (!empty($matchedKeys)) {
                Redis::connection($connection)->del($matchedKeys);
            }
        } catch (\Exception $e) {
            Log::error('SettingCacheException' . $e->getMessage());

            throw new SettingCacheException('Failed to delete keys.');
        }
    }

    /**
     * Check if a value exists in the settings cache.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->cacheManager->has($key);
    }

    /**
     * Check if a value is missing from the settings cache.
     *
     * @param string $key
     *
     * @return bool
     */
    public function missing(string $key): bool
    {
        return !$this->has($key);
    }

    /**
     * Invalidate a value in the settings cache.
     *
     * @param string $key
     *
     * @return void
     */
    public function invalidate(string $key): void
    {
        try {
            $this->cacheManager->forget($key);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            throw new SettingCacheException('Failed to invalidate cache KEY:' . $key);
        }
    }
}
