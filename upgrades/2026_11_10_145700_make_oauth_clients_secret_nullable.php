<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class MakeOauthClientsSecretNullable extends Upgrade
{
    /**
     * Run the upgrade migration.
     */
    public function up(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->string('secret', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the upgrade migration.
     */
    public function down(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->string('secret', 100)->nullable(false)->change();
        });
    }
};
