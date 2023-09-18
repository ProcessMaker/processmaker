<?php

use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class AddMissingUuidsToScriptExecutors extends Upgrade
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (ScriptExecutor::whereNull('uuid')->get() as $scriptExecutor) {
            $scriptExecutor->uuid = ScriptExecutor::generateUuid();
            $scriptExecutor->saveOrFail();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
}
