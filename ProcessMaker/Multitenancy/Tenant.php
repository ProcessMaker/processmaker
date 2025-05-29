<?php

namespace ProcessMaker\Multitenancy;

use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

class Tenant extends SpatieTenant
{
    protected $casts = [
        'config' => 'array',
    ];
}
