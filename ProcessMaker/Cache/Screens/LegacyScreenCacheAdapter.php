<?php

namespace ProcessMaker\Cache\Screens;

use DateInterval;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Cache\CacheInterface;
use ProcessMaker\Managers\ScreenCompiledManager;

class LegacyScreenCacheAdapter implements CacheInterface
{
    protected ScreenCompiledManager $compiledManager;

    public function __construct(ScreenCompiledManager $compiledManager)
    {
        $this->compiledManager = $compiledManager;
    }

    /**
     * Create a cache key for a screen
     */
    public function createKey(array $params): string
    {
        // Validate required parameters
        if (!isset($params['process_id'], $params['process_version_id'], $params['language'],
            $params['screen_id'], $params['screen_version_id'])) {
            throw new \InvalidArgumentException('Missing required parameters for screen cache key');
        }

        return $this->compiledManager->createKey(
            (string) $params['process_id'],
            (string) $params['process_version_id'],
            $params['language'],
            (string) $params['screen_id'],
            (string) $params['screen_version_id']
        );
    }

    /**
     * Get a screen from cache
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $content = $this->compiledManager->getCompiledContent($key);

        return $content ?? $default;
    }

    /**
     * Get a value from the cache, or store the value from the callback if it doesn't exist
     *
     * @param string $key The key to look up
     * @param callable $callback The callback that will return the value to store
     * @return mixed The value from cache or the callback
     * @throws \InvalidArgumentException
     */
    public function getOrCache(string $key, callable $callback): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value);

        return $value;
    }

    /**
     * Store a screen in cache
     *
     * @param string $key The key of the item to store
     * @param mixed $value The value of the item to store
     * @param DateInterval|int|null $ttl Optional TTL value
     * @return bool True on success and false on failure
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        // Note: The legacy compiled manager doesn't support TTL,
        // so we ignore the TTL parameter for backward compatibility
        $this->compiledManager->storeCompiledContent($key, $value);

        return true;
    }

    /**
     * Check if screen exists in cache
     */
    public function has(string $key): bool
    {
        return $this->compiledManager->getCompiledContent($key) !== null;
    }

    /**
     * Delete a screen from cache
     */
    public function delete(string $key): bool
    {
        return $this->compiledManager->deleteCompiledContent($key);
    }

    /**
     * Clear all screen caches
     */
    public function clear(): bool
    {
        return $this->compiledManager->clearCompiledContent();
    }

    /**
     * Check if screen is missing from cache
     */
    public function missing(string $key): bool
    {
        return !$this->has($key);
    }

    /**
     * Invalidate all cache entries for a specific screen
     *
     * @param int $screenId Screen ID
     * @return bool
     */
    public function invalidate($params): void
    {
        // Get all files from storage that match the pattern for this screen ID
        $screenId = $params['screen_id'];
        $language = $params['language'];
        $this->compiledManager->deleteScreenCompiledContent($screenId, $language);
    }

    /**
     * Clear all compiled screen assets
     */
    public function clearCompiledAssets(): void
    {
        $this->compiledManager->clearCompiledAssets();
    }
}
