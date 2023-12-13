<?php

use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class DeleteDuplicateEnvironmentVariables extends Upgrade
{
    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        // Temporary table to store the maximum id for each unique name
        $envVariables = DB::table('environment_variables')
            ->select(DB::raw('MAX(id) as max_id'))
            ->groupBy('name')
            ->get();

        // Delete rows with non-maximum id for each unique name
        DB::table('environment_variables')
            ->whereNotIn('id', $envVariables->pluck('max_id'))
            ->delete();

        // Delete rows where name is 'ess'
        EnvironmentVariable::where('name', 'ess')->delete();
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
