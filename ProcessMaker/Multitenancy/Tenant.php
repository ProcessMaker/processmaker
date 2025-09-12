<?php

namespace ProcessMaker\Multitenancy;

use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

class Tenant extends SpatieTenant
{
    const BOOTSTRAPPED_TENANT = 'bootstrappedTenant';

    protected $guarded = [];

    protected $casts = [
        'config' => 'array',
        'password' => 'encrypted',
    ];

    public static function fromBootstrapper()
    {
        return (new static())->newFromBuilder(app(self::BOOTSTRAPPED_TENANT));
    }
}
