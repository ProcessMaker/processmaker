<?php

use Database\Seeders\McpSystemSkillsSeeder;
use ProcessMaker\Mcp\McpGuidance;
use ProcessMaker\Models\Setting;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class AddMcpSettingsMenu extends Upgrade
{
    /**
     * Run the upgrade migration.
     */
    public function up(): void
    {
        (new McpSystemSkillsSeeder())->run();
    }

    /**
     * Reverse the upgrade migration.
     */
    public function down(): void
    {
        Setting::whereIn('key', [
            McpGuidance::SYSTEM_PROMPT_KEY,
            McpGuidance::SKILLS_KEY,
        ])->delete();
    }
}
