<?php

declare(strict_types=1);

namespace ProcessMaker\Cache\Settings;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Process-local (Octane worker / FPM worker) L1 cache for settings.
 *
 * Coordinated across workers via a Redis epoch key stored as a raw integer
 * (not PHP-serialized) so INCR stays atomic. When settings are published
 * (post-commit set / saved / delete), the epoch is incremented and the next
 * request/job on each worker drops its in-memory map.
 */
class SettingMemoryCache
{
    public const EPOCH_KEY = 'epoch';

    /**
     * In-memory settings keyed by tenant scope then cache key.
     *
     * @var array<string, array<string, mixed>>
     */
    private static array $items = [];

    /**
     * Last known epoch per tenant scope.
     *
     * @var array<string, int>
     */
    private static array $localEpochs = [];

    /**
     * Whether the Redis epoch was synced for the current request per tenant.
     *
     * @var array<string, bool>
     */
    private static array $syncedThisRequest = [];

    /**
     * When true, Redis epoch could not be read — skip L1/L2 and use DB.
     *
     * @var array<string, bool>
     */
    private static array $epochUnreliable = [];

    /**
     * Identifies the HTTP/console/job scope that last opened an L1 sync scope.
     */
    private static int|string|null $activeRequestToken = null;

    private static ?object $sentinel = null;

    /**
     * Mark the start of a request/job so the epoch is checked once (lazy) per tenant.
     */
    public static function beginRequest(): void
    {
        self::$syncedThisRequest = [];
        self::$epochUnreliable = [];
        self::$activeRequestToken = self::currentRequestToken();
    }

    /**
     * Sentinel object used to distinguish a cache miss from a cached null.
     */
    public static function sentinel(): object
    {
        return self::$sentinel ??= new class
        {
        };
    }

    public static function currentTenantKey(): string
    {
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;

        if ($tenant && isset($tenant->id)) {
            return (string) $tenant->id;
        }

        return 'landlord';
    }

    /**
     * Sync local epoch with Redis once per request/tenant; clear L1 on mismatch.
     */
    public static function syncEpoch(): void
    {
        self::ensureRequestScope();

        $tenantKey = self::currentTenantKey();

        if (!empty(self::$syncedThisRequest[$tenantKey])) {
            return;
        }

        $remote = self::fetchRemoteEpoch();

        if ($remote['unreliable']) {
            self::$items[$tenantKey] = [];
            self::$epochUnreliable[$tenantKey] = true;
            self::$syncedThisRequest[$tenantKey] = true;

            return;
        }

        self::$epochUnreliable[$tenantKey] = false;
        $remoteEpoch = $remote['epoch'];
        $localEpoch = self::$localEpochs[$tenantKey] ?? null;

        if ($localEpoch !== $remoteEpoch) {
            self::$items[$tenantKey] = [];
            self::$localEpochs[$tenantKey] = $remoteEpoch;
        }

        self::$syncedThisRequest[$tenantKey] = true;
    }

    /**
     * Force a fresh Redis epoch read (used to avoid publishing stale resolve fills).
     */
    public static function forceResyncEpoch(): void
    {
        $tenantKey = self::currentTenantKey();
        unset(self::$syncedThisRequest[$tenantKey]);
        self::syncEpoch();
    }

    public static function isEpochUnreliable(): bool
    {
        self::ensureRequestScope();

        return !empty(self::$epochUnreliable[self::currentTenantKey()]);
    }

    /**
     * Open a new sync scope when the HTTP request or job token changes.
     */
    private static function ensureRequestScope(): void
    {
        $token = self::currentRequestToken();

        if ($token === self::$activeRequestToken) {
            return;
        }

        self::$syncedThisRequest = [];
        self::$epochUnreliable = [];
        self::$activeRequestToken = $token;
    }

    private static function currentRequestToken(): int|string
    {
        if (app()->bound('request')) {
            $request = app('request');
            if (is_object($request)) {
                return spl_object_id($request);
            }
        }

        return 'console';
    }

    public static function exists(string $key): bool
    {
        $tenantKey = self::currentTenantKey();

        return array_key_exists($key, self::$items[$tenantKey] ?? []);
    }

    public static function get(string $key): mixed
    {
        $tenantKey = self::currentTenantKey();

        return self::$items[$tenantKey][$key] ?? null;
    }

    public static function put(string $key, mixed $value): void
    {
        $tenantKey = self::currentTenantKey();
        self::$items[$tenantKey][$key] = $value;
    }

    public static function forget(string $key): void
    {
        $tenantKey = self::currentTenantKey();
        unset(self::$items[$tenantKey][$key]);
    }

    public static function clearLocal(): void
    {
        $tenantKey = self::currentTenantKey();
        self::$items[$tenantKey] = [];
    }

    public static function clearAllLocal(): void
    {
        self::$items = [];
        self::$localEpochs = [];
        self::$syncedThisRequest = [];
        self::$epochUnreliable = [];
        self::$activeRequestToken = null;
    }

    /**
     * Increment the shared epoch and reset local L1 for the current tenant.
     */
    public static function bumpEpoch(): void
    {
        self::ensureRequestScope();

        $tenantKey = self::currentTenantKey();
        $newEpoch = self::incrementRemoteEpoch();

        self::$items[$tenantKey] = [];
        self::$localEpochs[$tenantKey] = $newEpoch;
        self::$epochUnreliable[$tenantKey] = false;
        self::$syncedThisRequest[$tenantKey] = true;
    }

    /**
     * After the Redis settings store was fully cleared, re-seed a raw epoch
     * strictly ahead of any plausible worker-local value.
     */
    public static function bumpEpochAfterStoreCleared(): void
    {
        self::ensureRequestScope();

        $tenantKey = self::currentTenantKey();
        $local = self::$localEpochs[$tenantKey] ?? 0;
        $newEpoch = max($local + 1, (int) (microtime(true) * 1000));

        try {
            self::redis()->set(self::epochRedisKey(), (string) $newEpoch);
        } catch (Throwable $e) {
            Log::info('SettingMemoryCache: failed to seed epoch after clear: ' . $e->getMessage());
        }

        self::$items[$tenantKey] = [];
        self::$localEpochs[$tenantKey] = $newEpoch;
        self::$epochUnreliable[$tenantKey] = false;
        self::$syncedThisRequest[$tenantKey] = true;
    }

    public static function localEpoch(): ?int
    {
        $tenantKey = self::currentTenantKey();

        return self::$localEpochs[$tenantKey] ?? null;
    }

    /**
     * @return array{unreliable: bool, epoch: int}
     */
    private static function fetchRemoteEpoch(): array
    {
        try {
            $value = self::redis()->get(self::epochRedisKey());

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

    private static function incrementRemoteEpoch(): int
    {
        try {
            return (int) self::redis()->incr(self::epochRedisKey());
        } catch (Throwable $e) {
            Log::info('SettingMemoryCache: failed to increment epoch: ' . $e->getMessage());
            $value = (self::$localEpochs[self::currentTenantKey()] ?? 0) + 1;

            try {
                self::redis()->set(self::epochRedisKey(), (string) $value);
            } catch (Throwable $inner) {
                Log::info('SettingMemoryCache: failed to write epoch: ' . $inner->getMessage());
            }

            return $value;
        }
    }

    private static function epochRedisKey(): string
    {
        $prefix = config('cache.stores.cache_settings.prefix', 'settings:');

        return $prefix . self::EPOCH_KEY;
    }

    private static function redis()
    {
        $connection = config('cache.stores.cache_settings.connection', 'cache_settings');

        return Redis::connection($connection);
    }
}
