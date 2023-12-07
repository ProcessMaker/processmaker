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
        DB::statement('delete from environment_variables
                        where id not in ( SELECT * FROM (select max(id)
                        from environment_variables group by name) AS S);
                    ');
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
