<?php

namespace ProcessMaker\Cache\Screens;

use Illuminate\Support\Facades\Storage;
use ProcessMaker\Managers\ScreenCompiledManager;

class LegacyScreenCacheAdapter implements ScreenCacheInterface
{
    protected ScreenCompiledManager $compiledManager;

    public function __construct(ScreenCompiledManager $compiledManager)
    {
        $this->compiledManager = $compiledManager;
    }

    /**
     * Create a cache key for a screen
     */
    public function createKey(int $processId, int $processVersionId, string $language, int $screenId, int $screenVersionId): string
    {
        return $this->compiledManager->createKey(
            (string) $processId,
            (string) $processVersionId,
            $language,
            (string) $screenId,
            (string) $screenVersionId
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
     * Store a screen in cache
     */
    public function set(string $key, mixed $value): bool
    {
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
    public function invalidate(int $screenId, string $language): bool
    {
        // Get all files from storage that match the pattern for this screen ID
        return $this->compiledManager->deleteScreenCompiledContent($screenId, $language);
    }
}
