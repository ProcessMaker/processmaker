<?php

namespace ProcessMaker\Providers;

use Illuminate\Support\ServiceProvider;
use ProcessMaker\Services\DataSourceIntegrations\DataSourceIntegrationsService;
use ProcessMaker\Services\DataSourceIntegrations\IntegrationsFactory;

class DataSourceIntegrationsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(IntegrationsFactory::class);
        $this->app->singleton(DataSourceIntegrationsService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
