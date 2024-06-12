<?php

use Illuminate\Support\Facades\Log;
use ProcessMaker\Models\Script;
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

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        try {
            $existsScriptsUsingNayra = Script::where('language', Base::NAYRA_LANG)->exists();
            if (!$existsScriptsUsingNayra) {
                ScriptExecutor::where('language', Base::NAYRA_LANG)->delete();
            } else {
                Log::error('There are scripts using Nayra, so the Nayra script executor cannot be deleted.');
            }
        } catch (Exception $e) {
            Log::error('Cannot delete Nayra script executor: ' . $e->getMessage());
        }
    }
}
