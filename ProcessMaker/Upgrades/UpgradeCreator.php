<?php

namespace ProcessMaker\Upgrades;

use Illuminate\Database\Migrations\MigrationCreator;

class UpgradeCreator extends MigrationCreator
{

    /**
     * Create a new upgrade migration file at the given path
     *
     * @param $name
     * @param $path
     * @param  null  $from
     * @param  null  $to
     * @param  null  $optional
     *
     * @return string
     */
    public function createUpgrade($name, $path, $from = null, $to = null, $optional = null)
    {
        $this->ensureMigrationDoesntAlreadyExist($name, $path);

        $this->files->put(
            $path = $this->getPath($name, $path),
            $this->populateUpgradeStub(
                $name, $this->getStub(null, null), $from, $to, $optional
            )
        );

        return $path;
    }

    /**
     * Overrides the default migration stubs path to return the
     * upgrade migration specific stubs directory path
     *
     * @return string
     */
    public function stubPath()
    {
        return base_path('stubs/upgrades');
    }

    /**
     * Populate the place-holders in the upgrade migration stub.
     *
     * @param $name
     * @param $stub
     * @param $from
     * @param $to
     * @param $optional
     *
     * @return array|string|string[]
     */
    protected function populateUpgradeStub($name, $stub, $from = null, $to = null, $optional = false)
    {
        $stub = str_replace('DummyUpgradeClass', $this->getClassName($name), $stub);

        if (! is_null($from)) {
            $stub = str_replace('protected $from = \'\';', 'protected $from = \''.$from.'\';', $stub);
        }

        if (! is_null($to)) {
            $stub = str_replace('protected $to = \'\';', 'protected $to = \''.$to.'\';', $stub);
        }

        if (true === $optional) {
            $stub = str_replace('protected $required = true;', 'protected $required = false;', $stub);
        }

        return $stub;
    }
}
