<?php

use ProcessMaker\Models\Screen;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class RemoveInterstitialKeys extends Upgrade
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
        $firstInterstitial = Screen::where('key', 'interstitial')
            ->where('description', 'Screen for the interstitial')
            ->orderBy('id', 'ASC')->first();
        if (!$firstInterstitial) {
            return;
        }
        Screen::where('key', 'interstitial')
            ->whereNot('id', $firstInterstitial->id)
            ->update(['key' => null]);
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
    }
}
