<?php

namespace ProcessMaker\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Migrations\Migrator;
use ProcessMaker\Upgrades\UpgradeMigrationRepository;
use ProcessMaker\Upgrades\UpgradeCreator;

class UpgradeMigrationServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerRepository();

        $this->registerMigrator();

        $this->registerCreator();
    }

    /**
     * Register the migration repository service.
     *
     * @return void
     */
    protected function registerRepository()
    {
        $this->app->singleton('upgrade-migrator.repository', function ($app) {
            $table = $app['config']['database.upgrade_migrations'];

            return new UpgradeMigrationRepository($app['db'], $table);
        });
    }

    /**
     * Register the migrator service.
     *
     * @return void
     */
    protected function registerMigrator()
    {
        // The migrator is responsible for actually running and rollback the migration
        // files in the application. We'll pass in our database connection resolver
        // so the migrator can resolve any of these connections when it needs to.
        $this->app->singleton('upgrade-migrator', function ($app) {
            $repository = $app['upgrade-migrator.repository'];

            return new Migrator($repository, $app['db'], $app['files']);
        });
    }

    /**
     * Register the migration creator.
     *
     * @return void
     */
    protected function registerCreator()
    {
        $this->app->singleton('upgrade-migrator.creator', function ($app) {
            return new UpgradeCreator($app['files']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['upgrade-migrator', 'upgrade-migrator.repository', 'upgrade-migrator.creator'];
    }
}
