<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class ChangeSettingsConfigToLongtext extends Upgrade
{
    /**
     * MCP skills catalog in settings.config exceeds MySQL TEXT (64KB).
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->longText('config')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->text('config')->nullable()->change();
        });
    }
}
