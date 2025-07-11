<?php

namespace ProcessMaker\Events;

use Spatie\Multitenancy\Contracts\IsTenant;

/**
 * This gets run after we attempt to find a tenant, weather we find one or not.
 *
 * It's used for code that must be run after we have a tenant.
 */
class TenantResolved
{
    public function __construct(public ?IsTenant $tenant = null)
    {
    }
}
