<?php

use Database\Seeders\McpSettingsSeeder;
use ProcessMaker\Mcp\McpGuidance;
use ProcessMaker\Models\Setting;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

/**
 * ProcessMaker no longer ships built-in MCP skills; they live in the Skills
 * repository (https://github.com/ProcessMaker/skills). Remove the system skills
 * previously stored in mcp.skills and keep every tenant custom skill.
 */
class RemoveMcpSystemSkills extends Upgrade
{
    private const SYSTEM_ID_PREFIX = 'mcp-system-';

    /**
     * Id of the screen creation skill stored by early local installs, before
     * system skills had a stable id.
     */
    private const LEGACY_SYSTEM_IDS = [
        'skill-1793976475109-l6si55',
    ];

    /**
     * Run the upgrade migration.
     */
    public function up(): void
    {
        $setting = Setting::where('key', McpGuidance::SKILLS_KEY)->first();
        if ($setting === null) {
            return;
        }

        $skills = is_array($setting->config) ? $setting->config : [];
        $custom = array_values(array_filter(
            $skills,
            fn ($skill) => !$this->isSystemSkill($skill)
        ));

        $setting->config = $custom;
        $setting->helper = McpSettingsSeeder::SKILLS_HELPER;
        $setting->saveOrFail();
    }

    /**
     * System skills cannot be restored from this repository, so there is
     * nothing to reverse. Custom skills are untouched.
     */
    public function down(): void
    {
        //
    }

    private function isSystemSkill(mixed $skill): bool
    {
        if (!is_array($skill)) {
            return false;
        }

        $id = trim((string) ($skill['id'] ?? ''));

        return !empty($skill['system'])
            || str_starts_with($id, self::SYSTEM_ID_PREFIX)
            || in_array($id, self::LEGACY_SYSTEM_IDS, true);
    }
}
