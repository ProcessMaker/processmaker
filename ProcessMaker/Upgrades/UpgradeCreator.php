<?php

namespace ProcessMaker\Upgrades;

use Illuminate\Database\Migrations\MigrationCreator;
use ProcessMaker\Exception\InvalidSemanticVersion;

class UpgradeCreator extends MigrationCreator
{
    /**
     * Create a new upgrade migration file at the given path
     *
     * @param $name
     * @param $path
     * @param  null  $to
     * @param  null  $optional
     *
     * @return string
     *
     * @throws \ProcessMaker\Exception\InvalidSemanticVersion
     */
    public function createUpgrade($name, $path)
    {
        $this->ensureMigrationDoesntAlreadyExist($name, $path);

        $this->files->put(
            $path = $this->getPath($name, $path),
            $this->populateUpgradeStub($name, $this->getStub(null, null))
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
     * @param  bool  $optional
     *
     * @return array|string|string[]
     */
    protected function populateUpgradeStub($name, $stub, $to = null, bool $optional = false)
    {
        return str_replace('DummyUpgradeClass', $this->getClassName($name), $stub);
    }
}
