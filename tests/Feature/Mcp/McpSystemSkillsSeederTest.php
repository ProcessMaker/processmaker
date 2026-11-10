<?php

namespace Tests\Feature\Mcp;

use Database\Seeders\McpSystemSkillsSeeder;
use ProcessMaker\Mcp\McpGuidance;
use ProcessMaker\Mcp\McpSystemSkills;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\SettingsMenus;
use Tests\TestCase;

class McpSystemSkillsSeederTest extends TestCase
{
    public function testSeederUpsertsSystemSkillAndKeepsCustomSkills(): void
    {
        SettingsMenus::firstOrCreate([
            'menu_group' => SettingsMenus::INTEGRATIONS_MENU_GROUP,
        ], [
            'menu_group_order' => 2,
            'ui' => json_encode(['icon' => 'puzzle-piece']),
        ]);

        Setting::factory()->create([
            'key' => McpGuidance::SKILLS_KEY,
            'format' => 'component',
            'config' => [
                [
                    'id' => 'skill-1793976475109-l6si55',
                    'name' => 'Legacy screen rules',
                    'slug' => 'skill-1793976475109-l6si55',
                    'when' => 'old',
                    'content' => 'legacy body',
                ],
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

        $this->seed(McpSystemSkillsSeeder::class);

        $skills = Setting::configByKey(McpGuidance::SKILLS_KEY);
        $this->assertIsArray($skills);

        $systemDefinitions = McpSystemSkills::definitions();
        $this->assertCount(count($systemDefinitions) + 1, $skills);

        $system = $skills[0];
        $this->assertSame(McpSystemSkills::SCREEN_CREATION_RULES_ID, $system['id']);
        $this->assertSame(McpSystemSkills::SCREEN_CREATION_RULES_SLUG, $system['slug']);
        $this->assertTrue($system['system']);
        $this->assertStringContainsString('VueComponentRenderTemplate', $system['content']);
        $this->assertStringContainsString('thin mount', $system['content']);
        $this->assertStringContainsString('dump form markup', $system['content']);
        $this->assertStringContainsString('"inspector": []', $system['content']);
        $this->assertStringContainsString('inside the Vue', $system['content']);
        $this->assertStringContainsString('Do **not** add native SB', $system['content']);
        $this->assertStringContainsString('clientSignature', $system['content']);

        $processCreation = collect($skills)->firstWhere('slug', McpSystemSkills::PROCESS_CREATION_RULES_SLUG);
        $this->assertNotNull($processCreation);
        $this->assertTrue($processCreation['system']);
        $this->assertStringContainsString('bpmn:Task', $processCreation['content']);
        $this->assertStringContainsString('${screen:', $processCreation['content']);
        $this->assertStringContainsString('Screen assignment (mandatory)', $processCreation['content']);
        $this->assertStringContainsString('unresolved_screen_ref', $processCreation['content']);

        $slugs = array_column($skills, 'slug');
        $this->assertContains('process-node-form-task', $slugs);
        $this->assertContains('process-validation', $slugs);
        $this->assertContains('process-web-entry', $slugs);
        $this->assertContains('data-connector-creation-rules', $slugs);
        $this->assertContains('data-connector-in-process', $slugs);
        $this->assertContains('collection-creation-rules', $slugs);
        $this->assertContains('collection-test-data', $slugs);

        $dataConnectorSkill = collect($skills)->firstWhere('slug', McpSystemSkills::DATA_CONNECTOR_CREATION_RULES_SLUG);
        $this->assertNotNull($dataConnectorSkill);
        $this->assertSame(McpGuidance::SCOPE_DATA_CONNECTORS, $dataConnectorSkill['scope']);
        $this->assertStringContainsString('endpoints', $dataConnectorSkill['content']);

        $collectionSkill = collect($skills)->firstWhere('slug', McpSystemSkills::COLLECTION_CREATION_RULES_SLUG);
        $this->assertNotNull($collectionSkill);
        $this->assertStringContainsString('select-list catalogs', $collectionSkill['content']);
        $this->assertStringContainsString('Vue SFC', $collectionSkill['content']);

        foreach ($systemDefinitions as $definition) {
            $this->assertNotSame('', $definition['content'], 'Empty content for ' . $definition['slug']);
        }

        $custom = $skills[count($systemDefinitions)];
        $this->assertSame('japanese-labels', $custom['slug']);
        $this->assertSame('Always use Japanese labels', $custom['content']);
        $this->assertArrayNotHasKey('system', $custom);

        // Re-run is idempotent and refreshes system content.
        $this->seed(McpSystemSkillsSeeder::class);
        $again = Setting::configByKey(McpGuidance::SKILLS_KEY);
        $this->assertCount(count($systemDefinitions) + 1, $again);
        $this->assertSame(McpSystemSkills::SCREEN_CREATION_RULES_SLUG, $again[0]['slug']);
        $this->assertSame('japanese-labels', $again[count($systemDefinitions)]['slug']);
    }
}
