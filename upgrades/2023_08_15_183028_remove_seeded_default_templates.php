<?php

use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Models\ProcessTemplates;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class RemoveSeededDefaultTemplates extends Upgrade
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        ProcessTemplates::where('key', 'default_templates')->where('user_id', null)->delete();
        Artisan::call('processmaker:sync-default-templates');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
