<?php

use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;
use ProcessMaker\Models\User;

return new class extends Upgrade {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        User::where('username', '_pm4_anon_user')->update([
            'timezone' => 'UTC'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        User::where('username', '_pm4_anon_user')->update([
            'timezone' => 'America/Los_Angeles'
        ]);
    }
};
