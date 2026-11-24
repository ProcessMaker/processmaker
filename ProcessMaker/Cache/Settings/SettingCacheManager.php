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

    /**
     * Envelope marker so cached null is distinguishable from a Laravel cache miss.
     */
    private const ENVELOPE_FLAG = '__pm_setting';

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
     * Create a cache key for a setting.
     *
     * @param array $params
     * @return string
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
        SettingMemoryCache::syncEpoch();

        $value = $default;

        if (!SettingMemoryCache::isEpochUnreliable()) {
            if (SettingMemoryCache::exists($key)) {
                $value = SettingMemoryCache::get($key);
            } else {
                $entry = $this->readStoreEntry($key);
                if ($entry['hit']) {
                    SettingMemoryCache::put($key, $entry['value']);
                    $value = $entry['value'];
                }
            }
        }

        return $value;
    }

    /**
     * Resolve a setting from L1, then Redis, then the loader (typically DB).
     *
     * @param string $key
     * @param callable $loader
     *
     * @return mixed
     */
    public function resolve(string $key, callable $loader): mixed
    {
        SettingMemoryCache::syncEpoch();

        // Freshness oracle unavailable: fail closed to DB, do not trust L1/L2.
        if (SettingMemoryCache::isEpochUnreliable()) {
            return $loader();
        }

        if (SettingMemoryCache::exists($key)) {
            return SettingMemoryCache::get($key);
        }

        return $this->resolveCacheMiss($key, $loader);
    }

    /**
     * Resolve after an L1 miss: Redis hit, or load + publish with epoch fencing.
     */
    private function resolveCacheMiss(string $key, callable $loader): mixed
    {
        $epochAtStart = SettingMemoryCache::localEpoch();
        $entry = $this->readStoreEntry($key);

        if ($entry['hit']) {
            SettingMemoryCache::put($key, $entry['value']);

            return $entry['value'];
        }

        $value = $this->loadAndPublish($key, $loader, $epochAtStart);
        SettingMemoryCache::put($key, $value);

        return $value;
    }

    /**
     * Load from the callback and write to Redis unless another worker published first.
     */
    private function loadAndPublish(string $key, callable $loader, ?int $epochAtStart): mixed
    {
        $value = $loader();

        // If another worker published a committed value while we loaded, do not
        // overwrite Redis with a possibly pre-commit row.
        SettingMemoryCache::forceResyncEpoch();
        if (SettingMemoryCache::isEpochUnreliable()) {
            return $value;
        }

        if (SettingMemoryCache::localEpoch() !== $epochAtStart) {
            $entry = $this->readStoreEntry($key);
            $value = $entry['hit'] ? $entry['value'] : $loader();
            if (!$entry['hit']) {
                $this->writeStoreEntry($key, $value);
            }
        } else {
            $this->writeStoreEntry($key, $value);
        }

        return $value;
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
     * Store a committed setting value, then publish a new epoch so other workers refresh L1.
     *
     * @param string $key
     * @param mixed $value
     * @param null|int|\DateInterval $ttl
     *
     * @return bool
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $result = $this->writeStoreEntry($key, $value, $ttl);

        if ($result) {
            SettingMemoryCache::bumpEpoch();
            SettingMemoryCache::put($key, $value);
        }

        return $result;
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
        $result = $this->cacheManager->forget($key);
        SettingMemoryCache::forget($key);
        SettingMemoryCache::bumpEpoch();

        return $result;
    }

    /**
     * Clear the settings cache.
     *
     * @return bool
     */
    public function clear(): bool
    {
        $result = $this->cacheManager->clear();
        SettingMemoryCache::bumpEpochAfterStoreCleared();

        return $result;
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

            SettingMemoryCache::bumpEpoch();
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
        SettingMemoryCache::syncEpoch();

        if (SettingMemoryCache::isEpochUnreliable()) {
            return false;
        }

        if (SettingMemoryCache::exists($key)) {
            return true;
        }

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
     * Drop a settings cache key from Redis/L1.
     *
     * By default does NOT bump the shared epoch — that must happen after the
     * committed value is published via set()/saved()/delete. Pass
     * bump_epoch=true for hard invalidations (e.g. deleted settings).
     *
     * @param array $params
     *
     * @return void
     */
    public function invalidate($params): void
    {
        try {
            $key = $params['key'];
            $bumpEpoch = (bool) ($params['bump_epoch'] ?? false);

            $this->cacheManager->forget($key);
            SettingMemoryCache::forget($key);

            if ($bumpEpoch) {
                SettingMemoryCache::bumpEpoch();
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            throw new SettingCacheException('Failed to invalidate cache KEY:' . $key);
        }
    }

    /**
     * Publish a new shared epoch without changing Redis setting payloads.
     */
    public function publishEpoch(): void
    {
        SettingMemoryCache::bumpEpoch();
    }

    /**
     * Expose the underlying cache store (used for tests).
     */
    public function store(): Repository
    {
        return $this->cacheManager;
    }

    /**
     * @return array{hit: bool, value: mixed}
     */
    private function readStoreEntry(string $key): array
    {
        $sentinel = SettingMemoryCache::sentinel();
        $stored = $this->cacheManager->get($key, $sentinel);

        if ($stored === $sentinel) {
            return ['hit' => false, 'value' => null];
        }

        if (is_array($stored) && ($stored[self::ENVELOPE_FLAG] ?? false) === true) {
            return ['hit' => true, 'value' => $stored['v']];
        }

        // Legacy unwrapped values written before envelopes.
        return ['hit' => true, 'value' => $stored];
    }

    private function writeStoreEntry(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $envelope = [
            self::ENVELOPE_FLAG => true,
            'v' => $value,
        ];

        return $this->cacheManager->put($key, $envelope, $ttl);
    }
}
