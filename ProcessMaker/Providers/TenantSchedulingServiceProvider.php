<?php

namespace ProcessMaker\Providers;

use Illuminate\Support\ServiceProvider;
use ProcessMaker\Multitenancy\Services\TenantSchedulingService;

class TenantSchedulingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(TenantSchedulingService::class, function ($app) {
            return new TenantSchedulingService();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
