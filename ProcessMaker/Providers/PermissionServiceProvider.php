<?php

namespace ProcessMaker\Providers;

use Illuminate\Support\ServiceProvider;
use ProcessMaker\Contracts\PermissionCacheInterface;
use ProcessMaker\Contracts\PermissionRepositoryInterface;
use ProcessMaker\Repositories\PermissionRepository;
use ProcessMaker\Services\PermissionCacheService;
use ProcessMaker\Services\PermissionServiceManager;

class PermissionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind interfaces to implementations
        $this->app->bind(PermissionRepositoryInterface::class, PermissionRepository::class);
        $this->app->bind(PermissionCacheInterface::class, PermissionCacheService::class);

        // Bind the service manager as a singleton
        $this->app->singleton(PermissionServiceManager::class, function ($app) {
            return new PermissionServiceManager(
                $app->make(PermissionRepositoryInterface::class),
                $app->make(PermissionCacheInterface::class)
            );
        });
    }
}
