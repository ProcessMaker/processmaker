<?php

namespace ProcessMaker\Multitenancy;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Throwable;

class TenantDomainCache
{
    public const CACHE_KEY = 'multitenancy.tenant_domains';

    public const TTL_SECONDS = 86400;

    public const STORE = 'landlord';

    /**
     * Domain => tenant id map for TrustHosts (and future resolve).
     *
     * @return array<string, int>
     */
    public static function map(): array
    {
        if (!config('app.multitenancy')) {
            return [];
        }

        try {
            $cached = self::store()->get(self::CACHE_KEY);
            if (is_array($cached)) {
                return $cached;
            }
        } catch (Throwable $e) {
            report($e);
        }

        try {
            $map = self::loadFromDatabase();
        } catch (QueryException $e) {
            // Transient landlord DB failure — do not cache an empty map.
            report($e);

            return [];
        }

        try {
            self::store()->put(self::CACHE_KEY, $map, self::TTL_SECONDS);
        } catch (Throwable $e) {
            report($e);
        }

        return $map;
    }

    /**
     * @return list<string>
     */
    public static function domains(): array
    {
        return array_keys(self::map());
    }

    public static function forget(): void
    {
        try {
            self::store()->forget(self::CACHE_KEY);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * @return array<string, int>
     */
    private static function loadFromDatabase(): array
    {
        return Tenant::query()
            ->whereNotNull('domain')
            ->where('domain', '!=', '')
            ->pluck('id', 'domain')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private static function store()
    {
        return Cache::store(self::STORE);
    }
}
