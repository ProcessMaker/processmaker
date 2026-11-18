<?php

namespace ProcessMaker\Multitenancy;

use Illuminate\Support\Facades\Crypt;
use ProcessMaker\Application;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

class Tenant extends SpatieTenant
{
    const BOOTSTRAPPED_TENANT = 'bootstrappedTenant';

    protected $guarded = [];

    // Non-persistent
    public $originalValues = null;

    protected $casts = [
        'config' => 'array',
        'password' => 'encrypted',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => TenantDomainCache::forget());
        static::deleted(fn () => TenantDomainCache::forget());
    }

    public static function setBootstrappedTenant(Application $app, ?array $tenantData)
    {
        $app->instance(self::BOOTSTRAPPED_TENANT, $tenantData);
    }

    public static function fromBootstrapper()
    {
        if (app()->has(self::BOOTSTRAPPED_TENANT)) {
            $tenant = (new static())->newFromBuilder(app(self::BOOTSTRAPPED_TENANT));
            $tenant->originalValues = app(self::BOOTSTRAPPED_TENANT)['original_values'];

            return $tenant;
        }

        return null;
    }

    public function getOriginalValue($key = null)
    {
        return $this->originalValues[$key] ?? null;
    }
}
