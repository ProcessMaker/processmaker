<?php

namespace ProcessMaker\Upgrades;

use ProcessMaker\Exception\InvalidSemanticVersion;
use Illuminate\Database\Migrations\MigrationCreator;

class UpgradeCreator extends MigrationCreator
{
    use ValidatesSemver;

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
    public function createUpgrade($name, $path, $to, $optional = null)
    {
        $this->ensureMigrationDoesntAlreadyExist($name, $path);

        if (!$this->validateSemver($to)) {
            throw new InvalidSemanticVersion("Invalid semantic version passed for the \"to\" argument: {$to}");
        }

        $this->files->put(
            $path = $this->getPath($name, $path),
            $this->populateUpgradeStub(
                $name, $this->getStub(null, null), $to, $optional ?? false
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
     * @param  bool  $optional
     *
     * @return array|string|string[]
     */
    protected function populateUpgradeStub($name, $stub, $to = null, bool $optional = false)
    {
        $stub = str_replace('DummyUpgradeClass', $this->getClassName($name), $stub);

        if (! is_null($to)) {
            $stub = str_replace('public $to = \'\';', 'public $to = \''.$to.'\';', $stub);
        }

        if (true === $optional) {
            $stub = str_replace('public $required = true;', 'public $required = false;', $stub);
        }

        return $stub;
    }
}
