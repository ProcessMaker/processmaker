<?php

namespace ProcessMaker\Upgrades;

use Throwable;
use Illuminate\Support\Arr;
use Composer\Semver\Comparator;
use Illuminate\Support\Collection;
use Illuminate\Database\Migrations\Migrator;
use ProcessMaker\Events\Upgrades\UpgradeMigrationEnded;
use ProcessMaker\Exception\UpgradeMigrationUnsuccessful;
use ProcessMaker\Events\Upgrades\UpgradeMigrationsEnded;
use ProcessMaker\Events\Upgrades\UpgradeMigrationStarted;
use ProcessMaker\Events\Upgrades\UpgradeMigrationsStarted;
use ProcessMaker\Events\Upgrades\NoPendingUpgradeMigrations;

class UpgradeMigrator extends Migrator
{
    use ValidatesSemver;

    /**
     * The app version (e.g. "4.2.28") we are upgrading to
     *
     * @var string
     */
    protected $to;

    /**
     * The current semantic app version
     *
     * @var string
     */
    protected $current;

    /**
     * Run the pending migrations at a given path.
     *
     * @param  array|string  $paths
     * @param  array  $options
     *
     * @return array
     */
    public function run($paths = [], array $options = [])
    {
        // First, we'll set the "to" version (the target version we're upgrading
        // to) and the "current" version as properties on this class instance
        $this->setVersions($options);

        // Once we grab all of the migration files for the path, we will compare them
        // against the migrations that have already been run for this package then
        // run each of the outstanding migrations against a database connection.
        $files = $this->getMigrationFiles($paths);

        $this->requireFiles($migrations = $this->pendingMigrations(
            $files, $this->repository->getRan()
        ));

        // Filter the pending upgrade migrations to ones compatible with the
        // current running semantic app version (as set in composer.json)
        // and the ones compatible with the request "to" semantic version
        $migrations = $this->versionCompatibleMigrations($migrations);

        // Run the remaining migrations
        $this->runPending($migrations, $options);

        return $migrations;
    }

    /**
     * Run an array of upgrade migrations.
     *
     * @param  array  $migrations
     * @param  array  $options
     *
     * @return void
     */
    public function runPending(array $migrations, array $options = [])
    {
        // First we will just make sure that there are any migrations to run. If there
        // aren't, we will just make a note of it to the developer so they're aware
        // that all of the migrations have been run against this database system.
        if (count($migrations) === 0) {
            $this->fireMigrationEvent(new NoPendingUpgradeMigrations('up'));

            $this->note('<info>No pending upgrade migrations.</info>');

            return;
        }

        // Next, we will get the next batch number for the migrations so we can insert
        // correct batch number in the database migrations repository when we store
        // each migration's execution. We will also extract a few of the options.
        $batch = $this->repository->getNextBatchNumber();

        $pretend = $options['pretend'] ?? false;

        $step = $options['step'] ?? false;

        $this->fireMigrationEvent(new UpgradeMigrationsStarted);

        // Once we have the array of migrations, we will spin through them and run the
        // migrations "up" so the changes are made to the databases. We'll then log
        // that the migration was run so we don't repeat it next time we execute.
        foreach ($migrations as $file) {
            try {
                $this->runUp($file, $batch, $pretend);
            } catch (UpgradeMigrationUnsuccessful $exception) {
                $this->note($exception->getMessage());

                break;
            }

            if ($step) {
                $batch++;
            }
        }

        $this->fireMigrationEvent(new UpgradeMigrationsEnded);
    }

    /**
     * Run "up" a migration instance.
     *
     * @param  string  $file
     * @param  int  $batch
     * @param  bool  $pretend
     *
     * @return void
     *
     * @throws \ProcessMaker\Exception\UpgradeMigrationUnsuccessful
     */
    protected function runUp($file, $batch, $pretend)
    {
        // First we will resolve a "real" instance of the migration class from this
        // migration file name. Once we have the instances we can run the actual
        // command such as "up" or "down", or we can just simulate the action.
        $migration = $this->resolve(
            $name = $this->getMigrationName($file)
        );

        if ($pretend) {
            return $this->pretendToRun($migration, 'up');
        }

        $this->note("<comment>Preflight Checks For:</comment> {$name}");

        $startTime = microtime(true);

        // Run the preflightChecks() method on the upgrade migration class
        // (if the method is present) and watch for exceptions. If one is
        // thrown, catch it and if the migration is optional, skip it,
        // otherwise throw an UpgradeMigrationUnsuccessful exception
        if (!$this->runPreflightChecks($migration)) {
            if (!$migration->required) {
                return;
            }

            throw new UpgradeMigrationUnsuccessful('<fg=red>Upgrades Migrations Halted:</> One or more preflight checks failed');
        }

        $this->note("<comment>Upgrading:</comment> {$name}");

        try {
            $this->runMigration($migration, 'up');
        } catch (Throwable $exception) {
            $this->note("|-- <fg=red>Upgrades Migration Failed:</> {$exception->getMessage()}");

            if (!$migration->required) {
                return;
            }

            throw new UpgradeMigrationUnsuccessful('<fg=red>Upgrades Migrations Halted:</> A required upgrade migration failed to run');
        }

        $runTime = round(microtime(true) - $startTime, 2);

        // Once we have run a migrations class, we will log that it was run in this
        // repository so that we don't try to run it next time we do a migration
        // in the application. A migration repository keeps the migrate order.
        $this->repository->log($name, $batch);

        $this->note("<info>Upgraded:</info>  {$name} ({$runTime} seconds)");
    }

    /**
     * Run a migration inside a transaction if the database supports it.
     *
     * @param  object  $migration
     * @param  string  $method
     *
     * @return void
     * @throws \Throwable
     */
    protected function runMigration($migration, $method)
    {
        if (!method_exists($migration, $method)) {
            return;
        }

        $connection = $this->resolveConnection(
            $migration->getConnection()
        );

        $callback = function () use ($migration, $method) {
            if (!method_exists($migration, $method)) {
                return;
            }

            $this->fireMigrationEvent(new UpgradeMigrationStarted($migration, $method));

            $migration->{$method}();

            $this->fireMigrationEvent(new UpgradeMigrationEnded($migration, $method));
        };

        $this->getSchemaGrammar($connection)->supportsSchemaTransactions() && $migration->withinTransaction
            ? $connection->transaction($callback)
            : $callback();
    }

    /**
     * @param $migration
     * @param $name
     *
     * @return bool
     */
    protected function runPreflightChecks($migration)
    {
        if (!method_exists($migration, 'preflightChecks')) {
            return true;
        }

        // Required migrations can't be skipped if the preflight checks fail, and
        // each upgrade migration will throw a RuntimeException if the preflight
        // check fails, so if we encounter one on a required upgrade migration,
        // we'll note the exception and then throw an exception to stop the
        // other upgrade migrations from running.
        try {
            $migration->preflightChecks();
        } catch (Throwable $exception) {
            $this->note("|-- <fg=red>Preflight Checks Failed:</> {$exception->getMessage()}");

            return false;
        }

        return true;
    }

    /**
     * Rollback the last migration operation.
     *
     * @param  array|string  $paths
     * @param  array  $options
     * @return array
     */
    public function rollback($paths = [], array $options = [])
    {
        // We want to pull in the last batch of migrations that ran on the previous
        // migration operation. We'll then reverse those migrations and run each
        // of them "down" to reverse the last migration "operation" which ran.
        $migrations = $this->getMigrationsForRollback($options);

        if (count($migrations) === 0) {
            $this->fireMigrationEvent(new NoPendingUpgradeMigrations('down'));

            $this->note('<info>No upgrade migrations to rollback.</info>');

            return [];
        }

        return $this->rollbackMigrations($migrations, $paths, $options);
    }


    /**
     * Rollback the given migrations.
     *
     * @param  array  $migrations
     * @param  array|string  $paths
     * @param  array  $options
     * @return array
     */
    protected function rollbackMigrations(array $migrations, $paths, array $options)
    {
        $rolledBack = [];

        $this->requireFiles($files = $this->getMigrationFiles($paths));

        $this->fireMigrationEvent(new UpgradeMigrationsStarted);

        // Next we will run through all of the migrations and call the "down" method
        // which will reverse each migration in order. This getLast method on the
        // repository already returns these migration's names in reverse order.
        foreach ($migrations as $migration) {
            $migration = (object) $migration;

            if (!$file = Arr::get($files, $migration->upgrade)) {
                $this->note("<fg=red>Upgrade migration not found:</> {$migration->upgrade}");

                continue;
            }

            $rolledBack[] = $file;

            $this->runDown($file, $migration, false);
        }

        $this->fireMigrationEvent(new UpgradeMigrationsEnded);

        return $rolledBack;
    }

    /**
     * Run "down" a migration instance.
     *
     * @param  string  $file
     * @param  object  $migration
     * @param  bool  $pretend
     * @return void
     */
    protected function runDown($file, $migration, $pretend)
    {
        // First we will get the file name of the migration so we can resolve out an
        // instance of the migration. Once we get an instance we can either run a
        // pretend execution of the migration or we can run the real migration.
        $instance = $this->resolve(
            $name = $this->getMigrationName($file)
        );

        $this->note("<comment>Rolling back:</comment> {$name}");

        if ($pretend) {
            return $this->pretendToRun($instance, 'down');
        }

        $startTime = microtime(true);

        $this->runMigration($instance, 'down');

        $runTime = round(microtime(true) - $startTime, 2);

        // Once we have successfully run the migration "down" we will remove it from
        // the migration repository so it will be considered to have not been run
        // by the application then will be able to fire by any later operation.
        $this->repository->delete($migration);

        $this->note("<info>Rolled back:</info>  {$name} ({$runTime} seconds)");
    }

    /**
     * Get an array of version-compatible upgrade migrations
     *
     * @param  array  $migrations
     *
     * @return array
     */
    protected function versionCompatibleMigrations(array $migrations = [])
    {
        return Collection::make($migrations)->reject(function ($file) {
            // Get an instance of the migration file class
            $upgrade = $this->resolve(
                $this->getMigrationName($file)
            );

            // Filter out upgrade migrations which are out of the range for the current running
            // version of the app. For example, if we are running 4.2.28 then any upgrade
            // migration with a "to" property of a version that's equal to or less than
            // this version is compatible. This is because the migrations run in order
            // of the version they were created for. We also need to filter out any
            // migrations which are less than the requested $to version.
            return Comparator::lessThan($this->current, $upgrade->to)
                || Comparator::lessThanOrEqualTo($this->to, $upgrade->to);
        })->values()->all();
    }

    /**
     * @param  array  $versions
     *
     * @return void
     */
    protected function setVersions(array $versions)
    {
        if (array_key_exists('current', $versions)) {
            $this->setCurrentVersion($versions['current']);
        }

        if (array_key_exists('to', $versions)) {
            $this->setToVersion($versions['to']);
        }

        if (blank($this->to)) {
            $this->setToVersion($this->current);
        }
    }

    /**
     * @param $to string
     *
     * @return void
     */
    public function setToVersion($to)
    {
        if (is_string($to)) {
            $this->to = $to;
        }
    }

    /**
     * @param $current string
     *
     * @return void
     */
    public function setCurrentVersion($current)
    {
        if (is_string($current)) {
            $this->current = $current;
        }
    }
}
