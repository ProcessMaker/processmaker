<?php

// use DB;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

require_once(base_path('database/migrations/2022_07_11_202603_add_uuid_to_exportable_resources.php'));

class AddUuids extends Upgrade
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
        foreach(AddUuidToExportableResources::TABLES as $table) {
            if (!Schema::hasTable($table)) {
                throw new \RuntimeException("Table '$table' does not exist. Skipping upgrade.");
            }
        }
    }

    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        foreach(AddUuidToExportableResources::TABLES as $table) {
           DB::table($table)
            ->select('id')
            ->where('uuid', '=', null)
            ->orderBy('id')->chunk(1000, function($rows) use ($table) {
                $count = count($rows);
                foreach ($rows as $row) {
                    $uuid = (string) Str::orderedUuid();
                    DB::statement("update `$table` set `uuid` = ? where `id` = ?", [$uuid, $row->id]);
                }
            });
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

