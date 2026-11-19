<?php

declare(strict_types=1);

namespace ProcessMaker\Cache\Settings;

use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Process-local (Octane worker / FPM worker) L1 cache for settings.
 *
 * Coordinated across workers via a Redis epoch key: when settings are
 * invalidated, the epoch is incremented and the next request on each worker
 * drops its in-memory map.
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

    private static ?object $sentinel = null;

    /**
     * Mark the start of a request so the epoch is checked once (lazy) per tenant.
     */
    public static function beginRequest(): void
    {
        self::$syncedThisRequest = [];
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
    public static function syncEpoch(Repository $store): void
    {
        $tenantKey = self::currentTenantKey();

        if (!empty(self::$syncedThisRequest[$tenantKey])) {
            return;
        }

        $remoteEpoch = self::fetchRemoteEpoch($store);
        $localEpoch = self::$localEpochs[$tenantKey] ?? null;

        if ($localEpoch !== $remoteEpoch) {
            self::$items[$tenantKey] = [];
            self::$localEpochs[$tenantKey] = $remoteEpoch;
        }

        self::$syncedThisRequest[$tenantKey] = true;
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

    /**
     * Clear L1 for the current tenant scope only.
     */
    public static function clearLocal(): void
    {
        $tenantKey = self::currentTenantKey();
        self::$items[$tenantKey] = [];
    }

    /**
     * Clear all tenant scopes (tests / full reset).
     */
    public static function clearAllLocal(): void
    {
        self::$items = [];
        self::$localEpochs = [];
        self::$syncedThisRequest = [];
    }

    /**
     * Increment the shared epoch and reset local L1 for the current tenant.
     */
    public static function bumpEpoch(Repository $store): void
    {
        $tenantKey = self::currentTenantKey();
        $newEpoch = self::incrementRemoteEpoch($store);

        self::$items[$tenantKey] = [];
        self::$localEpochs[$tenantKey] = $newEpoch;
        self::$syncedThisRequest[$tenantKey] = true;
    }

    /**
     * After the Redis settings store was fully cleared, re-seed the epoch.
     */
    public static function bumpEpochAfterStoreCleared(Repository $store): void
    {
        $tenantKey = self::currentTenantKey();
        $newEpoch = (self::$localEpochs[$tenantKey] ?? 0) + 1;

        try {
            $store->forever(self::EPOCH_KEY, $newEpoch);
        } catch (Throwable $e) {
            Log::info('SettingMemoryCache: failed to seed epoch after clear: ' . $e->getMessage());
        }

        self::$items[$tenantKey] = [];
        self::$localEpochs[$tenantKey] = $newEpoch;
        self::$syncedThisRequest[$tenantKey] = true;
    }

    public static function localEpoch(): ?int
    {
        $tenantKey = self::currentTenantKey();

        return self::$localEpochs[$tenantKey] ?? null;
    }

    private static function fetchRemoteEpoch(Repository $store): int
    {
        try {
            return (int) $store->get(self::EPOCH_KEY, 0);
        } catch (Throwable $e) {
            Log::info('SettingMemoryCache: failed to read epoch: ' . $e->getMessage());

            return self::$localEpochs[self::currentTenantKey()] ?? 0;
        }
    }

    private static function incrementRemoteEpoch(Repository $store): int
    {
        try {
            $value = $store->increment(self::EPOCH_KEY);
            if ($value === false) {
                $value = (self::$localEpochs[self::currentTenantKey()] ?? 0) + 1;
                $store->forever(self::EPOCH_KEY, $value);
            }

            return (int) $value;
        } catch (Throwable $e) {
            Log::info('SettingMemoryCache: failed to increment epoch: ' . $e->getMessage());
            $value = (self::$localEpochs[self::currentTenantKey()] ?? 0) + 1;

            try {
                $store->forever(self::EPOCH_KEY, $value);
            } catch (Throwable $inner) {
                Log::info('SettingMemoryCache: failed to write epoch: ' . $inner->getMessage());
            }

            return $value;
        }
    }
}
