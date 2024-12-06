<?php

namespace ProcessMaker\Cache\Screens;

interface ScreenCacheInterface
{
    /**
     * Create a cache key for a screen
     */
    public function createKey(int $processId, int $processVersionId, string $language, int $screenId, int $screenVersionId): string;

    /**
     * Get a screen from cache
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store a screen in cache
     */
    public function set(string $key, mixed $value): bool;

    /**
     * Check if screen exists in cache
     *
     * @param string $key Screen cache key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Delete a screen from cache
     *
     * @param string $key Screen cache key
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * Clear all screen caches
     *
     * @return bool
     */
    public function clear(): bool;

    /**
     * Check if screen is missing from cache
     *
     * @param string $key Screen cache key
     * @return bool
     */
    public function missing(string $key): bool;

    /**
     * Invalidate cache for a specific screen
     *
     * @param int $screenId
     * @return bool
     */
    public function invalidate(
        int $screenId,
        string $language,
    ): bool;
}
