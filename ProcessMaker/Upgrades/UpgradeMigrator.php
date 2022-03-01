<?php

namespace ProcessMaker\Upgrades;

use Illuminate\Support\Str;
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
        } else {
            $this->setToVersion($this->current);
        }
    }

    /**
     * @param  array  $migrations
     *
     * @return array
     */
    protected function compatibleMigrations(array $migrations): array
    {
        return Collection::make($migrations)->reject(function ($file) {
            $migration = $this->resolve(
                $this->getMigrationName($file)
            );

            return Comparator::lessThanOrEqualTo(
                $this->to,
                $migration->to
            );
        })->values()->all();
    }

    public function setToVersion(string $to)
    {
        $this->to = $to;
    }

    public function setCurrentVersion(string $current)
    {
        $this->current = $current;
    }
}
