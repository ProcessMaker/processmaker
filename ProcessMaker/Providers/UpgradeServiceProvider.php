<?php

namespace ProcessMaker\Providers;

use Illuminate\Support\ServiceProvider;
use ProcessMaker\Upgrades\Commands as Commands;
use ProcessMaker\Upgrades\UpgradeCreator;
use ProcessMaker\Upgrades\UpgradeMigrationRepository;
use ProcessMaker\Upgrades\UpgradeMigrator;

class UpgradeServiceProvider extends ServiceProvider
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
    protected static $commands = [
        Commands\UpgradeCommand::class => 'command.upgrade',
        Commands\UpgradeInstallCommand::class => 'command.upgrade.install',
        Commands\UpgradeRollbackCommand::class => 'command.upgrade.rollback',
        Commands\UpgradeResetCommand::class => 'command.upgrade.reset',
        Commands\UpgradeStatusCommand::class => 'command.upgrade.status',
        Commands\UpgradeMakeCommand::class => 'command.upgrade.make',
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands();

        $this->registerRepository();

        $this->registerMigrator();

        $this->registerCreator();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_merge(
            array_values(self::$commands), ['upgrade', 'upgrade.repository', 'upgrade.creator']
        );
    }

    /**
     * Register the migration repository service.
     *
     * @return void
     */
    protected function registerRepository()
    {
        $this->app->singleton('upgrade.repository', function ($app) {
            $table = $app['config']['database.upgrades'];

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
        $this->app->singleton('upgrade', function ($app) {
            $repository = $app['upgrade.repository'];

            return new UpgradeMigrator($repository, $app['db'], $app['files']);
        });
    }

    /**
     * Register the migration creator.
     *
     * @return void
     */
    protected function registerCreator()
    {
        $this->app->singleton('upgrade.creator', function ($app) {
            return new UpgradeCreator($app['files'], $app->basePath('stubs'));
        });
    }

    /**
     * Register the given commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        foreach (array_keys(self::$commands) as $command) {
            call_user_func_array([$this, 'register'.class_basename($command)], []);
        }

        $this->commands(array_values(self::$commands));
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerUpgradeCommand()
    {
        $this->app->singleton('command.upgrade', function ($app) {
            return new Commands\UpgradeCommand($app['upgrade']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerUpgradeInstallCommand()
    {
        $this->app->singleton('command.upgrade.install', function ($app) {
            return new Commands\UpgradeInstallCommand($app['upgrade.repository']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerUpgradeMakeCommand()
    {
        $this->app->singleton('command.upgrade.make', function ($app) {
            // Once we have the upgrade creator registered, we will create the command
            // and inject the creator. The creator is responsible for the actual file
            // creation of the data-migrations, and may be extended by these developers.
            $creator = $app['upgrade.creator'];

            $composer = $app['composer'];

            return new Commands\UpgradeMakeCommand($creator, $composer);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerUpgradeResetCommand()
    {
        $this->app->singleton('command.upgrade.reset', function ($app) {
            return new Commands\UpgradeResetCommand($app['upgrade']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerUpgradeRollbackCommand()
    {
        $this->app->singleton('command.upgrade.rollback', function ($app) {
            return new Commands\UpgradeRollbackCommand($app['upgrade']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerUpgradeStatusCommand()
    {
        $this->app->singleton('command.upgrade.status', function ($app) {
            return new Commands\UpgradeStatusCommand($app['upgrade']);
        });
    }
}
