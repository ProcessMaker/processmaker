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
            return self::store()->remember(self::CACHE_KEY, self::TTL_SECONDS, function () {
                return self::loadFromDatabase();
            });
        } catch (Throwable $e) {
            report($e);

            return self::loadFromDatabase();
        }
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
        try {
            return Tenant::query()
                ->whereNotNull('domain')
                ->where('domain', '!=', '')
                ->pluck('id', 'domain')
                ->map(fn ($id) => (int) $id)
                ->all();
        } catch (QueryException $e) {
            return [];
        }
    }

    private static function store()
    {
        return Cache::store(self::STORE);
    }
}
