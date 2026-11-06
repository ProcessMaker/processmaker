<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use ProcessMaker\Mcp\McpGuidance;
use ProcessMaker\Mcp\McpSystemSkills;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\SettingsMenus;

class McpSystemSkillsSeeder extends Seeder
{
    /**
     * Ensure MCP settings exist and upsert built-in system skills without
     * removing custom skills tenants add in Admin → Settings → MCP.
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
            'helper' => 'Skills catalog for MCP clients. System skills are built-in; add custom skills here. Only name/when is sent at connect; full markdown loads on demand via get-mcp-skill.',
            'group' => 'MCP',
            'group_id' => $groupId,
            'hidden' => false,
            'ui' => [
                'order' => 200,
                'component' => 'McpSkills',
            ],
        ]);

        $skillsSetting->helper = 'Skills catalog for MCP clients. System skills are built-in; add custom skills here. Only name/when is sent at connect; full markdown loads on demand via get-mcp-skill.';
        $skillsSetting->config = $this->mergeSystemSkills(
            is_array($skillsSetting->config) ? $skillsSetting->config : []
        );
        $skillsSetting->saveOrFail();

        Setting::reguard();
    }

    /**
     * @param list<mixed> $existing
     * @return list<array<string, mixed>>
     */
    private function mergeSystemSkills(array $existing): array
    {
        $reserved = array_flip(McpSystemSkills::reservedKeys());
        $custom = [];
        foreach ($existing as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = trim((string) ($item['id'] ?? ''));
            $slug = trim((string) ($item['slug'] ?? ''));
            if (isset($reserved[$id]) || isset($reserved[$slug]) || !empty($item['system'])) {
                continue;
            }
            $custom[] = $item;
        }

        return array_values([...McpSystemSkills::definitions(), ...$custom]);
    }
}
