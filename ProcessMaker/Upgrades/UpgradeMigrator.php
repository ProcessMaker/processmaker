<?php

namespace ProcessMaker\Upgrades;

use RuntimeException;
use Composer\Semver\Comparator;
use Illuminate\Support\Collection;
use Illuminate\Database\Migrations\Migrator;

class UpgradeMigrator extends Migrator
{
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
        $migrations = $this->compatibleMigrations($migrations);

        // Once we have all these migrations that are outstanding we are ready to run
        // we will go ahead and run them "up". This will execute each migration as
        // an operation against a database. Then we'll return this list of them.
        $this->runPending($migrations, $options);

        return $migrations;
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

        if ($pretend) {
            return $this->pretendToRun($migration, 'up');
        }

        $this->note("<comment>Preflight Checks:</comment> {$name}");

        $startTime = microtime(true);

        if (!$this->runMigrationPreflightChecks($migration, $name)) {
            if (!$migration->required) {
                return;
            }

            throw new RuntimeException('Upgrade migrations halted: One or more preflight checks failed');
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
     * @param $migration
     * @param $name
     *
     * @return bool
     */
    protected function runMigrationPreflightChecks($migration, $name)
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
            $this->note("<comment>Preflight Checks Failed:</comment> {$name}");
            $this->note("<comment>Preflight Checks Failed Reason:</comment> {$exception->getMessage()}");

            return false;
        }

        return true;
    }

    /**
     * @param  array  $migrations
     *
     * @return array
     */
    protected function compatibleMigrations(array $migrations): array
    {
        return Collection::make($migrations)->reject(function ($file) {
            return Comparator::lessThanOrEqualTo(
                $this->to,
                $this->getMigrationToVersion($file)
            );
        })->values()->all();
    }

    /**
     * @param  string  $file
     *
     * @return mixed
     */
    protected function getMigrationToVersion(string $file)
    {
        $migration = $this->resolve(
            $this->getMigrationName($file)
        );

        return $migration->to;
    }

    /**
     * @param  array  $options
     *
     * @return void
     */
    protected function setVersions(array $options)
    {
        if (array_key_exists('current', $options)) {
            $this->setCurrentVersion($options['current']);
        }

        if (array_key_exists('to', $options)) {
            $this->setToVersion($options['to']);
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
