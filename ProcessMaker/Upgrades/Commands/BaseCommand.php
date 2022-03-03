<?php

namespace ProcessMaker\Upgrades\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Upgrades\ValidatesSemver;
use ProcessMaker\Exception\InvalidSemanticVersion;
use function config;
use function collect;
use function base_path;

class BaseCommand extends Command
{
    use ValidatesSemver;

    /**
     * Get all of the migration paths.
     *
     * @return array
     */
    protected function getMigrationPaths()
    {
        // Here, we will check to see if a path option has been defined. If it has we will
        // use the path relative to the root of the installation folder so our database
        // migrations may be run for any customized path from within the application.
        if ($this->input->hasOption('path') && $this->option('path')) {
            return collect($this->option('path'))->map(function ($path) {
                return $this->laravel->basePath().'/'.$path;
            })->all();
        }

        return array_merge(
            [$this->getMigrationPath()], $this->migrator->paths()
        );
    }

    /**
     * Get the current running version of ProcessMaker (as set in composer.json)
     *
     * @return string|void
     */
    protected function getCurrentAppVersion()
    {
        $composer = json_decode(file_get_contents(base_path('/composer.json')), false);

        if (property_exists($composer, 'version')) {
            return $composer->version;
        }
    }

    /**
     * Get the version we're upgrading or rolling back to
     *
     * @return string|void
     *
     * @throws \RuntimeException
     * @throws \ProcessMaker\Exception\InvalidSemanticVersion
     */
    protected function getMigratingToVersion()
    {
        if (!$this->hasOption('to')) {
            return;
        }

        if (!is_string($to = $this->option('to'))) {
            return;
        }

        if (!$this->validateSemver($to)) {
            throw new InvalidSemanticVersion('The --to option must be a valid semantic version');
        }

        return $to;
    }

    /**
     * Get the database name the upgrades will use
     *
     * @return string
     */
    protected function getDatabase()
    {
        if ($this->hasOption('database') && $database = $this->option('database')) {
            return $database;
        }

        return (string) config('database.connections.processmaker.database');
    }

    /**
     * Get the path to the migration directory.
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        return base_path('upgrades');
    }
}
