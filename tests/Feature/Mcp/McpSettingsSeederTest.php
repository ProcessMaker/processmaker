<?php

namespace Tests\Feature\Mcp;

use Database\Seeders\McpSettingsSeeder;
use ProcessMaker\Mcp\McpGuidance;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\SettingsMenus;
use Tests\TestCase;

class McpSettingsSeederTest extends TestCase
{
    private function createIntegrationsMenu(): void
    {
        SettingsMenus::firstOrCreate([
            'menu_group' => SettingsMenus::INTEGRATIONS_MENU_GROUP,
        ], [
            'menu_group_order' => 2,
            'ui' => json_encode(['icon' => 'puzzle-piece']),
        ]);
    }

    public function testSeederCreatesEmptySettingsWithoutBuiltInSkills(): void
    {
        $this->createIntegrationsMenu();

        $this->seed(McpSettingsSeeder::class);

        $this->assertSame([], Setting::configByKey(McpGuidance::SKILLS_KEY));
        $this->assertNotNull(Setting::where('key', McpGuidance::SYSTEM_PROMPT_KEY)->first());
    }

    public function testSeederKeepsExistingCustomSkillsAndSystemPrompt(): void
    {
        $this->createIntegrationsMenu();

        Setting::factory()->create([
            'key' => McpGuidance::SYSTEM_PROMPT_KEY,
            'format' => 'textarea',
            'config' => 'Always be brief',
            'name' => 'System Prompt',
            'group' => 'MCP',
        ]);
        Setting::factory()->create([
            'key' => McpGuidance::SKILLS_KEY,
            'format' => 'component',
            'config' => [
                [
                    'id' => 'custom-1',
                    'name' => 'Japanese labels',
                    'slug' => 'japanese-labels',
                    'when' => 'Before create-screen',
                    'content' => 'Always use Japanese labels',
                ],
            ],
            'name' => 'Skills',
            'group' => 'MCP',
            'ui' => ['component' => 'McpSkills'],
        ]);

        $this->seed(McpSettingsSeeder::class);
        $this->seed(McpSettingsSeeder::class);

        $skills = Setting::configByKey(McpGuidance::SKILLS_KEY);
        $this->assertCount(1, $skills);
        $this->assertSame('custom-1', $skills[0]['id']);
        $this->assertSame('Always be brief', Setting::configByKey(McpGuidance::SYSTEM_PROMPT_KEY));
    }
}
