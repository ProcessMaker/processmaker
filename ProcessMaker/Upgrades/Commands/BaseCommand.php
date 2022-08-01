<?php

namespace ProcessMaker\Upgrades\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BaseCommand extends Command
{
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
     * Get the path to the migration directory.
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        File::ensureDirectoryExists($path = base_path('upgrades'));

        return $path;
    }
}
