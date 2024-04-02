<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessLaunchpad;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class PopulateProcessLaunchpad extends Upgrade
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
        // Populate
        $chunkSize = 50;
        Process::whereNotNull('launchpad_properties')->chunk($chunkSize, function ($processes) {
            foreach ($processes as $process) {
                $this->updateLaunchpadProperties($process);
            }
        });
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        // Truncate the table settings_menus
        DB::table('process_launchpad')->truncate();
    }

    /**
     * Populate the table process_launchpad
     */
    private function updateLaunchpadProperties(Process $process): void
    {
        DB::table('process_launchpad')->insert([
            'uuid' => ProcessLaunchpad::generateUuid(),
            'process_id' => $process->id,
            'user_id' => $process->user_id,
            'launchpad_properties' => $process->launchpad_properties,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Log::info("Process Launchpad process_id {$process->id} migrated successfully");
    }
}
