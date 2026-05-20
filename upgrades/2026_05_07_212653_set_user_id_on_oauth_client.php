<?php

use Illuminate\Support\Facades\DB;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class SetUserIdOnOauthClient extends Upgrade
{
    /**
     * Run any validations/pre-run checks to ensure the environment, settings,
     * packages installed, etc. are right correct to run this upgrade.
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
     * @throws RuntimeException
     */
    public function preflightChecks()
    {
        //
    }

    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        $adminUserId = DB::table('users')
            ->where('is_administrator', true)
            ->orderBy('id')
            ->value('id');

        if ($adminUserId === null) {
            return;
        }

        DB::table('oauth_clients')
            ->where('personal_access_client', true)
            ->whereNull('user_id')
            ->update(['user_id' => $adminUserId]);
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        $adminUserId = DB::table('users')
            ->where('is_administrator', true)
            ->orderBy('id')
            ->value('id');

        if ($adminUserId === null) {
            return;
        }

        DB::table('oauth_clients')
            ->where('personal_access_client', true)
            ->where('user_id', $adminUserId)
            ->update(['user_id' => null]);
    }
}
