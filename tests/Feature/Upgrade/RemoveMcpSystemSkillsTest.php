<?php

namespace Tests\Feature\Upgrade;

use ProcessMaker\Mcp\McpGuidance;
use ProcessMaker\Models\Setting;
use Tests\TestCase;

class RemoveMcpSystemSkillsTest extends TestCase
{
    private function runUpgrade(): void
    {
        require_once base_path('upgrades/2026_11_11_190000_remove_mcp_system_skills.php');
        (new \RemoveMcpSystemSkills())->up();
    }

    public function testRemovesSystemSkillsAndKeepsCustomSkills(): void
    {
        Setting::factory()->create([
            'key' => McpGuidance::SKILLS_KEY,
            'format' => 'component',
            'config' => [
                ['id' => 'mcp-system-screen-creation-rules', 'slug' => 'screen-creation-rules', 'system' => true],
                ['id' => 'flagged-system', 'slug' => 'flagged', 'system' => true],
                ['id' => 'skill-1793976475109-l6si55', 'slug' => 'skill-1793976475109-l6si55'],
                ['id' => 'custom-1', 'name' => 'Japanese labels', 'slug' => 'japanese-labels', 'content' => 'x'],
                ['id' => 'custom-2', 'name' => 'Tone', 'slug' => 'tone', 'content' => 'y', 'scope' => 'screens'],
            ],
            'name' => 'Skills',
            'group' => 'MCP',
            'ui' => ['component' => 'McpSkills'],
        ]);

        $this->runUpgrade();

        $skills = Setting::configByKey(McpGuidance::SKILLS_KEY);
        $this->assertSame(['custom-1', 'custom-2'], array_column($skills, 'id'));
        $this->assertSame('screens', $skills[1]['scope']);
    }

    public function testIsIdempotentAndSkipsMissingSetting(): void
    {
        $this->runUpgrade();
        $this->assertNull(Setting::where('key', McpGuidance::SKILLS_KEY)->first());

        Setting::factory()->create([
            'key' => McpGuidance::SKILLS_KEY,
            'format' => 'component',
            'config' => [['id' => 'custom-1', 'slug' => 'a', 'content' => 'x']],
            'name' => 'Skills',
            'group' => 'MCP',
        ]);

        $this->runUpgrade();
        $this->runUpgrade();

        $this->assertCount(1, Setting::configByKey(McpGuidance::SKILLS_KEY));
    }
}
