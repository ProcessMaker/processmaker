<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Models\Embed;
use ProcessMaker\Models\ProcessLaunchpad;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class PopulateNameLaunchpadEmbed extends Upgrade
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
     * @throws \RuntimeException
     */
    public function preflightChecks()
    {
        // Add preflightChecks
    }

    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        $chunkSize = 50;
        // Populate
        ProcessLaunchpad::where('name', '')->update([
            'name' => DB::raw('CONCAT(uuid, "_launchpad")'),
        ]);
        Embed::where('name', '')->update([
            'name' => DB::raw('CONCAT(uuid, "_embed")'),
        ]);
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        DB::table('process_launchpad')->update([
            'name' => '',
        ]);
        DB::table('embed')->update([
            'name' => 'null',
        ]);
    }
}
