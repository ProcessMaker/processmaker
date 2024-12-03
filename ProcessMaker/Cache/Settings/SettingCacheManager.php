<?php

namespace ProcessMaker\Cache\Settings;

use Exception;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Cache\CacheInterface;

class SettingCacheManager implements CacheInterface
{
    protected CacheManager $cacheManager;

    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
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
     * @param callable|null $callback
     *
     * @return mixed
     */
    public function get(string $key, callable $callback = null): mixed
    {
        $value = $this->cacheManager->get($key);

        if ($value) {
            return $value;
        }

        if ($callback === null) {
            return null;
        }

        $value = $callback();

        if ($value === null) {
            throw new \InvalidArgumentException('The key does not exist.');
        }

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
        return $this->cacheManager->flush();
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
}
