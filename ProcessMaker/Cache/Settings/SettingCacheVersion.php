<?php

declare(strict_types=1);

namespace ProcessMaker\Cache\Settings;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Per-key generation counters for settings cache envelopes.
 *
 * The shared epoch only flushes process-local L1. These counters fence L2 so a
 * stale resolve() PUT stamped with an older generation cannot stick after
 * set()/invalidate()/delete advances the key.
 */
class SettingCacheVersion
{
    /**
     * Read the current generation for a settings cache key (0 if missing).
     */
    public static function get(string $key): int
    {
        try {
            $value = self::connection()->get(self::key($key));

            return $value === null || $value === false ? 0 : (int) $value;
        } catch (Throwable $e) {
            Log::info('SettingCacheVersion: failed to read version: ' . $e->getMessage());

            return 0;
        }
    }

    /**
     * Atomically advance the generation for a settings cache key.
     */
    public static function increment(string $key): int
    {
        try {
            return (int) self::connection()->incr(self::key($key));
        } catch (Throwable $e) {
            Log::info('SettingCacheVersion: failed to increment version: ' . $e->getMessage());

            $fallback = self::get($key) + 1;

            try {
                self::connection()->set(self::key($key), (string) $fallback);
            } catch (Throwable $inner) {
                Log::info('SettingCacheVersion: failed to write version: ' . $inner->getMessage());
            }

            return $fallback;
        }
    }

    public static function key(string $cacheKey): string
    {
        $prefix = config('cache.stores.cache_settings.prefix', 'settings:');

        return $prefix . 'ver:' . $cacheKey;
    }

    private static function connection()
    {
        $connection = config('cache.stores.cache_settings.connection', 'cache_settings');

        return Redis::connection($connection);
    }
}
