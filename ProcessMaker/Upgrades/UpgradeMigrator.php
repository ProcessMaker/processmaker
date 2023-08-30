<?php

namespace ProcessMaker\Upgrades;

use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Arr;
use RuntimeException;

class UpgradeMigrator extends Migrator
{
    /**
     * Reset the given migrations.
     *
     * @param  array  $migrations
     * @param  array  $paths
     * @param  bool  $pretend
     *
     * @return array
     * @throws \Throwable
     */
    protected function resetMigrations(array $migrations, array $paths, $pretend = false)
    {
        // Since the getRan method that retrieves the migration name just gives us the
        // migration name, we will format the names into objects with the name as a
        // property on the objects so that we can pass it to the rollback method.
        $migrations = collect($migrations)->map(function ($m) {
            return (object) ['upgrade' => $m];
        })->all();

        return $this->rollbackMigrations(
            $migrations, $paths, compact('pretend')
        );
    }

    /**
     * Run "up" a migration instance.
     *
     * @param  string  $file
     * @param  int  $batch
     * @param  bool  $pretend
     *
     * @return void
     */
    protected function runUp($file, $batch, $pretend)
    {
        // First we will resolve a "real" instance of the migration class from this
        // migration file name. Once we have the instances we can run the actual
        // command such as "up" or "down", or we can just simulate the action.
        $migration = $this->resolve(
            $name = $this->getMigrationName($file)
        );

        if (!$pretend) {
            $this->note("<comment>Preflight Check:</comment> {$name}");
        }

        $startTime = microtime(true);

        // Run the preflightChecks() method on the upgrade migration class
        // (if the method is present) and watch for exceptions. If one is
        // thrown, catch it and if the migration is optional, skip it,
        // otherwise throw an UpgradeMigrationUnsuccessful exception
        if (!$this->runPreflightChecks($migration, $name)) {
            return $this->note("|-- <comment>Skipping</comment>: {$name}");
        }

        $this->note("<info>Preflight Check Successful:</info> {$name}");

        if ($pretend) {
            return;
        }

        $this->note("<comment>Upgrading:</comment> {$name}");

        $this->runMigration($migration, 'up');

        $runTime = round(microtime(true) - $startTime, 2);

        // Once we have run a migrations class, we will log that it was run in this
        // repository so that we don't try to run it next time we do a migration
        // in the application. A migration repository keeps the migrate order.
        $this->repository->log($name, $batch);

        $this->note("<info>Upgraded:</info>  {$name} ({$runTime} seconds)");
    }

    /**
     * Run "down" a migration instance.
     *
     * @param  string  $file
     * @param  object  $migration
     * @param  bool  $pretend
     *
     * @return void
     *
     * @throws \Throwable
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
            return;
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
     * Rollback the given upgrade migrations.
     *
     * @param  array  $migrations
     * @param  array|string  $paths
     * @param  array  $options
     *
     * @return array
     *
     * @throws \Throwable
     */
    protected function rollbackMigrations(array $migrations, $paths, array $options)
    {
        $rolledBack = [];

        $this->requireFiles($files = $this->getMigrationFiles($paths));

        $this->fireMigrationEvent(new MigrationsStarted('down'));

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

            $this->runDown(
                $file, $migration,
                $options['pretend'] ?? false
            );
        }

        $this->fireMigrationEvent(new MigrationsEnded('down'));

        return $rolledBack;
    }

    /**
     * @param $migration
     * @param $name
     *
     * @return bool
     */
    protected function runPreflightChecks($migration, $name)
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
        } catch (RuntimeException $exception) {
            $this->note("<fg=red>Preflight Check Failed:</> {$name}");
            $this->note("|-- <comment>Failure Message:</comment> {$exception->getMessage()}");

            // Exception code of 1 means we should exit and
            // stop the upgrade migrations from running
            if ($exception->getCode() === 1) {
                exit(1);
            }

            return false;
        }

        return true;
    }

    private function note($note)
    {
        $this->output->writeln($note);
    }
}
