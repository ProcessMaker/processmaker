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
     */
    public function has(string $key): bool;
}
