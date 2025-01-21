<?php

namespace ProcessMaker\Providers;

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $resolver = $this->app->make(ConnectionResolverInterface::class);
        $table = $this->app['config']['database.migrations'];
        $this->app->bind(MigrationRepositoryInterface::class, function ($app) use ($resolver, $table) {
            return new DatabaseMigrationRepository($resolver, $table);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
