<?php

use ProcessMaker\Models\Screen;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class FixInterstitialKey extends Upgrade
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
        if (Screen::where('key', 'interstitial')->count() === 0) {
            // get screen where config (json column) 0.name equals "Screen Interstitial"
            if ($screen = Screen::whereRaw('JSON_EXTRACT(config, "$[0].name") = "Screen Interstitial"')->first()) {
                $screen->key = 'interstitial';
                $screen->save();
            } else {
                throw new Exception('Screen Interstitial not found');
            }
        }
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
