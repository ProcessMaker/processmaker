<?php

namespace ProcessMaker\Upgrades;

use Illuminate\Database\Migrations\Migration;

abstract class UpgradeMigration extends Migration
{
    /**
     * The version of ProcessMaker being upgraded *to*
     *
     * @var string example: 4.2.28
     */
    public $to = '';

    /**
     * Upgrades migration cannot be skipped if the pre-upgrade checks fail
     *
     * @var bool
     */
    public $required = true;

    /**
     * Run any validations/pre-run checks to ensure the environment, settings,
     * packages installed, etc. are right correct to run this upgrade.
     *
     * There is no need to check against the version(s) as the upgrade
     * migrator will do this automatically and fail if the correct
     * version(s) are not present.
     *
     * Throw a RuntimeException if the conditions to run this upgrade migration
     * are *NOT* correct. If this is not a required upgrade, then it will be
     * skipped. Otherwise, the thrown exception will stop the remaining
     * upgrade migrations from running.
     *
     * @return void
     */
    protected function preflightChecks()
    {
        //
    }
}
