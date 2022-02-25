<?php

namespace ProcessMaker\Providers;

use Illuminate\Support\ServiceProvider;
use ProcessMaker\Upgrades\Commands\UpgradeCommand;
use ProcessMaker\Upgrades\Commands\UpgradeMakeCommand;
use ProcessMaker\Upgrades\Commands\UpgradeResetCommand as MigrateResetCommand;
use ProcessMaker\Upgrades\Commands\UpgradeStatusCommand as MigrateStatusCommand;
use ProcessMaker\Upgrades\Commands\UpgradeInstallCommand as MigrateInstallCommand;
use ProcessMaker\Upgrades\Commands\UpgradeRefreshCommand as MigrateRefreshCommand;
use ProcessMaker\Upgrades\Commands\UpgradeRollbackCommand as MigrateRollbackCommand;

class ArtisanServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'Migrate' => 'command.upgrade-migrate',
        'MigrateInstall' => 'command.upgrade-migrate.install',
        'MigrateRefresh' => 'command.upgrade-migrate.refresh',
        'MigrateReset' => 'command.upgrade-migrate.reset',
        'MigrateRollback' => 'command.upgrade-migrate.rollback',
        'MigrateStatus' => 'command.upgrade-migrate.status',
        'MigrateMake' => 'command.upgrade-migrate.make',
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands(
            $this->commands
        );
    }

    /**
     * Register the given commands.
     *
     * @param  array  $commands
     * @return void
     */
    protected function registerCommands(array $commands)
    {
        foreach (array_keys($commands) as $command) {
            call_user_func_array([$this, "register{$command}Command"], []);
        }

        $this->commands(array_values($commands));
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateCommand()
    {
        $this->app->singleton('command.upgrade-migrate', function ($app) {
            return new UpgradeCommand($app['upgrade-migrator']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateInstallCommand()
    {
        $this->app->singleton('command.upgrade-migrate.install', function ($app) {
            return new MigrateInstallCommand($app['upgrade-migrator.repository']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateMakeCommand()
    {
        $this->app->singleton('command.upgrade-migrate.make', function ($app) {
            // Once we have the upgrade-migrator creator registered, we will create the command
            // and inject the creator. The creator is responsible for the actual file
            // creation of the data-migrations, and may be extended by these developers.
            $creator = $app['upgrade-migrator.creator'];

            $composer = $app['composer'];

            return new UpgradeMakeCommand($creator, $composer);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateRefreshCommand()
    {
        $this->app->singleton('command.upgrade-migrate.refresh', function () {
            return new MigrateRefreshCommand;
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateResetCommand()
    {
        $this->app->singleton('command.upgrade-migrate.reset', function ($app) {
            return new MigrateResetCommand($app['upgrade-migrator']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateRollbackCommand()
    {
        $this->app->singleton('command.upgrade-migrate.rollback', function ($app) {
            return new MigrateRollbackCommand($app['upgrade-migrator']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateStatusCommand()
    {
        $this->app->singleton('command.upgrade-migrate.status', function ($app) {
            return new MigrateStatusCommand($app['upgrade-migrator']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_values($this->commands);
    }
}
