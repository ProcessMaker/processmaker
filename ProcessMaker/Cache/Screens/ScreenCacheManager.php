<?php

namespace ProcessMaker\Cache\Screens;

use Illuminate\Cache\CacheManager;
use ProcessMaker\Cache\CacheInterface;
use ProcessMaker\Managers\ScreenCompiledManager;
use ProcessMaker\Models\Screen;

class ScreenCacheManager implements CacheInterface
{
    protected CacheManager $cacheManager;

    protected ScreenCompiledManager $screenCompiler;

    /**
     * Default TTL for cached screens (24 hours)
     */
    protected const DEFAULT_TTL = 86400;

    public function __construct(CacheManager $cacheManager, ScreenCompiledManager $screenCompiler)
    {
        $this->cacheManager = $cacheManager;
        $this->screenCompiler = $screenCompiler;
    }

    public function createKey(array $params): string
    {
        // Validate required parameters
        if (!isset($params['process_id'], $params['process_version_id'], $params['language'],
            $params['screen_id'], $params['screen_version_id'])) {
            throw new \InvalidArgumentException('Missing required parameters for screen cache key');
        }

        return sprintf(
            'screen_pid_%d_%d_%s_sid_%d_%d',
            $params['process_id'],
            $params['process_version_id'],
            $params['language'],
            $params['screen_id'],
            $params['screen_version_id']
        );
    }

    /**
     * Get a screen from cache
     *
     * @param string $key Screen cache key
     * @param mixed $default Default value
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $serializedContent = $this->cacheManager->get($key);
        if ($serializedContent !== null) {
            return unserialize($serializedContent);
        }

        return $default;
    }

    /**
     * Get a screen from cache, or store the value from the callback if the key exists
     *
     * @param string $key Screen cache key
     * @param callable $callback Callback to generate screen content
     * @param null|int|\DateInterval $ttl Time to live
     * @return mixed
     */
    public function getOrCache(string $key, callable $callback, null|int|\DateInterval $ttl = null): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();

        if ($value === null) {
            return $value;
        }

        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Store a screen in memory cache
     *
     * @param string $key Screen cache key
     * @param mixed $value Compiled screen content
     * @param null|int|\DateInterval $ttl Time to live
     * @return bool
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $serializedContent = serialize($value);

        return $this->cacheManager->put($key, $serializedContent, $ttl ?? self::DEFAULT_TTL);
    }

    /**
     * Delete a screen from cache
     *
     * @param string $key Screen cache key
     * @return bool
     */
    public function delete(string $key): bool
    {
        return $this->cacheManager->forget($key);
    }

    /**
     * Clear all screen caches
     *
     * @return bool
     */
    public function clear(): bool
    {
        return $this->cacheManager->flush();
    }

    /**
     * Check if screen exists in cache
     *
     * @param string $key Screen cache key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->cacheManager->has($key);
    }

    /**
     * Check if screen is missing
     *
     * @param string $key Screen cache key
     * @return bool
     */
    public function missing(string $key): bool
    {
        return !$this->has($key);
    }

    /**
     * Invalidate all cache entries for a specific screen
     * @param int $screenId Screen ID
     * @param string $language Language code
     * @return bool
     */
    public function invalidate($params): void
    {
        // Get all keys from cache that match the pattern for this screen ID
        // TODO Improve this to avoid scanning the entire cache
        //extract the params from the array
        $screenId = $params['screen_id'];
        $language = $params['language'];
        $pattern = "*_{$language}_sid_{$screenId}_*";
        $keys = $this->cacheManager->get($pattern);

        // Delete all matching keys
        foreach ($keys as $key) {
            $this->cacheManager->forget($key);
        }
    }
}
