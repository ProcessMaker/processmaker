<?php

declare(strict_types=1);

namespace ProcessMaker\Cache\Settings;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Raw Redis I/O for the shared settings epoch key (integer, not PHP-serialized).
 */
class SettingEpochRedis
{
    /**
     * @return array{unreliable: bool, epoch: int}
     */
    public static function get(): array
    {
        try {
            $value = self::connection()->get(self::key());

            return [
                'unreliable' => false,
                'epoch' => $value === null || $value === false ? 0 : (int) $value,
            ];
        } catch (Throwable $e) {
            Log::info('SettingMemoryCache: failed to read epoch: ' . $e->getMessage());

            return [
                'unreliable' => true,
                'epoch' => 0,
            ];
        }
    }

    /**
     * Atomically increment the shared epoch; on failure, seed with $fallback.
     */
    public static function incrementOrFallback(int $fallback): int
    {
        try {
            return (int) self::connection()->incr(self::key());
        } catch (Throwable $e) {
            Log::info('SettingMemoryCache: failed to increment epoch: ' . $e->getMessage());

            try {
                self::set($fallback);
            } catch (Throwable $inner) {
                Log::info('SettingMemoryCache: failed to write epoch: ' . $inner->getMessage());
            }

            return $fallback;
        }
    }

    public static function set(int $epoch): void
    {
        self::connection()->set(self::key(), (string) $epoch);
    }

    public static function key(): string
    {
        $prefix = config('cache.stores.cache_settings.prefix', 'settings:');

        return $prefix . SettingMemoryCache::EPOCH_KEY;
    }

    private static function connection()
    {
        $connection = config('cache.stores.cache_settings.connection', 'cache_settings');

        return Redis::connection($connection);
    }
}
