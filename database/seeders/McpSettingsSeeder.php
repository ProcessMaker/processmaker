<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use ProcessMaker\Mcp\McpGuidance;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\SettingsMenus;

class McpSettingsSeeder extends Seeder
{
    public const SKILLS_HELPER = 'Custom skills for MCP clients. Only name/when is sent at connect; full markdown loads on demand via get-mcp-skill. ProcessMaker platform skills are installed from the Skills repository (npx skills add processmaker/skills).';

    /**
     * Ensure the MCP settings exist. Tenant custom skills are never modified.
     */
    public function run(): void
    {
        $groupId = SettingsMenus::getId(SettingsMenus::INTEGRATIONS_MENU_GROUP);

        Setting::unguard();

        Setting::firstOrCreate(['key' => McpGuidance::SYSTEM_PROMPT_KEY], [
            'format' => 'textarea',
            'config' => '',
            'name' => 'System Prompt',
            'helper' => 'Short global guidance injected into every ProcessMaker MCP session (all clients).',
            'group' => 'MCP',
            'group_id' => $groupId,
            'hidden' => false,
            'ui' => ['order' => 100],
        ]);

        $skillsSetting = Setting::firstOrCreate(['key' => McpGuidance::SKILLS_KEY], [
            'format' => 'component',
            'config' => [],
            'name' => 'Skills',
            'helper' => self::SKILLS_HELPER,
            'group' => 'MCP',
            'group_id' => $groupId,
            'hidden' => false,
            'ui' => [
                'order' => 200,
                'component' => 'McpSkills',
            ],
        ]);

        if ($skillsSetting->helper !== self::SKILLS_HELPER) {
            $skillsSetting->helper = self::SKILLS_HELPER;
            $skillsSetting->saveOrFail();
        }

        Setting::reguard();
    }
}
