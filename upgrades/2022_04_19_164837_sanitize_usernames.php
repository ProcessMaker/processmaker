<?php

use Illuminate\Support\Facades\DB;
use ProcessMaker\Jobs\SanitizeUsernames as SanitizeUsernamesJob;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class SanitizeUsernames extends Upgrade
{
    public $to = '4.2.30-RC1';

    /**
     * Run any validations/pre-run checks to ensure the environment, settings,
     * packages installed, etc. are right correct to run this upgrade.
     *
     * There is no need to check against the version(s) as the upgrade
     * migrator will do this automatically and fail if the correct
     * version(s) are not present.
     *
     * Throw a \RuntimeException if the conditions are *NOT* correct for this
     * upgrade migration to run. If this is not a required upgrade, then it
     * will be skipped. Otherwise the exception thrown will be caught, noted,
     * and will prevent the remaining migrations from continuing to run.
     *
     * Returning void or null denotes the checks were successful.
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function preflightChecks()
    {
        //
    }

    /**
     * Run the upgrade migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('users')->select(['id', 'username'])->orderBy('id')->chunk(250,
            static function ($users) {
                dispatch(new SanitizeUsernamesJob($users))->onQueue('high');
            }
        );
    }

    /**
     * Reverse the upgrade migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
