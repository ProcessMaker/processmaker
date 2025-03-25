<?php

namespace ProcessMaker\Cache\Settings;

use Illuminate\Cache\CacheManager;
use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use ProcessMaker\Cache\CacheInterface;
use ProcessMaker\Cache\CacheManagerBase;

class SettingCacheManager extends CacheManagerBase implements CacheInterface
{
    const DEFAULT_CACHE_DRIVER = 'cache_settings';

    const DEFAULT_CACHE_PREFIX = 'settings:';

    protected CacheManager $manager;

    protected Repository $cacheManager;

    public function __construct(CacheManager $cacheManager)
    {
        $this->manager = $cacheManager;
        $this->setCacheDriver();
    }

    /**
     * Determine and set the cache driver to use.
     *
     * @param CacheManager $cacheManager
     *
     * @return void
     */
    private function setCacheDriver(): void
    {
        $defaultCache = config('cache.default');
        $isAvailableConnection = in_array($defaultCache, self::AVAILABLE_CONNECTIONS);

        // Set the cache driver to use
        $cacheDriver = $isAvailableConnection ? self::DEFAULT_CACHE_DRIVER : $defaultCache;
        // Store the cache driver
        $this->cacheManager = $this->manager->store($cacheDriver);
    }

    /**
     * Create a cache key for a screen
     *
     * @param int $processId Process ID
     * @param int $processVersionId Process Version ID
     * @param string $language Language code
     * @param int $screenId Screen ID
     * @param int $screenVersionId Screen Version ID
     * @return string The generated cache key
     */
    public function createKey(array $params): string
    {
        // Validate required parameters
        if (!isset($params['key'])) {
            throw new \InvalidArgumentException('Missing required parameters for settings cache key');
        }

        return sprintf(
            'setting_%s',
            $params['key']
        );
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
        //get the default driver
        $defaultDriver = config('cache.stores.cache_settings.connection') ?? self::DEFAULT_CACHE_DRIVER;
        try {
            //get the prefix
            $prefix = config('cache.stores.cache_settings.prefix', self::DEFAULT_CACHE_PREFIX);
            // Filter keys by pattern
            $matchedKeys = $this->getKeysByPattern($pattern, $defaultDriver, $prefix);

            if (!empty($matchedKeys)) {
                Redis::connection($defaultDriver)->del($matchedKeys);
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
        return $this->keyExists($key);
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
    public function invalidate($params): void
    {
        try {
            //extract the params from the array
            $key = $params['key'];
            $this->cacheManager->forget($key);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            throw new SettingCacheException('Failed to invalidate cache KEY:' . $key);
        }
    }
}
