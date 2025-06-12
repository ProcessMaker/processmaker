<?php

namespace ProcessMaker\Multitenancy;

use Illuminate\Support\ServiceProvider;
use ProcessMaker\Multitenancy\Commands\TenantsArtisanCommand;

class MultitenancyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register any bindings or dependencies here
        $this->app->singleton('currentTenant', function ($app) {
            return null; // Default to no tenant
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                TenantsArtisanCommand::class,
            ]);
        }
    }
}
