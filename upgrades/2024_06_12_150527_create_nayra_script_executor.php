<?php

use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\ScriptRunners\Base;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class CreateNayraScriptExecutor extends Upgrade
{

    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        $exists = ScriptExecutor::where('language', Base::NAYRA_LANG)->exists();
        if (!$exists) {
            $scriptExecutor = new ScriptExecutor();
            $scriptExecutor->language = Base::NAYRA_LANG;
            $scriptExecutor->title = 'Nayra (µService)';
            $scriptExecutor->save();
        }
    }
}
