<?php

namespace ProcessMaker\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use ProcessMaker\Helpers\TenantLicense;
use Spatie\Multitenancy\Events\MadeTenantCurrentEvent;

abstract class ProcessMakerBaseServiceProvider extends ServiceProvider
{
    public const name = '';

    public function boot()
    {
        Event::listen(MadeTenantCurrentEvent::class, function () {
            // diferent of empty string
            if (static::name !== '' || !TenantLicense::hasFeature(static::name)) {
                return; // Don't register anything for this package for this tenant
            }
            $this->bootTenant();
        });
    }

    abstract public function bootTenant(): void;
}
