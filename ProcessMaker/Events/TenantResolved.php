<?php

namespace ProcessMaker\Events;

use Spatie\Multitenancy\Contracts\IsTenant;

/**
 * This gets run if we find a tenant, or, if multitenancy is disabled.
 *
 * It's used for code that must be run after we have a tenant.
 */
class TenantResolved
{
    public function __construct(public ?IsTenant $tenant = null)
    {
    }
}
