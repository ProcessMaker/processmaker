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
        $this->assertContains('process-connector-email', $slugs);
        $this->assertContains('process-connector-pdf', $slugs);
        $this->assertContains('process-connector-slack', $slugs);
        $this->assertContains('process-connector-decision-task', $slugs);
        $this->assertContains('process-connector-flowgenie', $slugs);
        $this->assertContains('process-connector-uipath', $slugs);
        $this->assertContains('process-connector-actions-by-email', $slugs);
        $this->assertContains('process-connector-docusign', $slugs);
        $this->assertContains('process-node-pools', $slugs);
        $this->assertContains('screen-vue-fields', $slugs);
        $this->assertContains('screen-refactor-parity', $slugs);

        $emailConnector = collect($skills)->firstWhere('slug', 'process-connector-email');
        $this->assertNotNull($emailConnector);
        $this->assertSame(McpGuidance::SCOPE_PROCESSES, $emailConnector['scope']);
        $this->assertStringContainsString('connector-send-email/processmaker-communication-email-send', $emailConnector['content']);
        $this->assertStringContainsString('toRecipients', $emailConnector['content']);

        $poolsSkill = collect($skills)->firstWhere('slug', 'process-node-pools');
        $this->assertNotNull($poolsSkill);
        $this->assertStringContainsString('bpmn:Participant', $poolsSkill['content']);
        $this->assertStringContainsString('messageFlow', $poolsSkill['content']);

        $refactorParity = collect($skills)->firstWhere('slug', 'screen-refactor-parity');
        $this->assertNotNull($refactorParity);
        $this->assertStringContainsString('conditionalHide', $refactorParity['content']);
        $this->assertStringContainsString('always hidden', $refactorParity['content']);
        $this->assertStringContainsString('data-only', $refactorParity['content']);
        $this->assertStringContainsString('Native SB control', $refactorParity['content']);
        $this->assertStringContainsString('Must preserve', $refactorParity['content']);
        $this->assertStringContainsString('UI redesign', $refactorParity['content']);
        $this->assertStringContainsString('Nested screens', $refactorParity['content']);
        $this->assertStringContainsString('shared Vue', $refactorParity['content']);
        $this->assertStringContainsString('Instructional / help content', $refactorParity['content']);
        $this->assertStringContainsString('FormImage', $refactorParity['content']);

        $vueFields = collect($skills)->firstWhere('slug', 'screen-vue-fields');
        $this->assertNotNull($vueFields);
        $this->assertTrue($vueFields['system']);
        $this->assertSame(McpGuidance::SCOPE_SCREENS, $vueFields['scope']);
        $this->assertStringContainsString('VueComponentRenderTemplate', $vueFields['content']);
        $this->assertStringContainsString('executePmScript', $vueFields['content']);
        $this->assertStringContainsString('screen-refactor-parity', $vueFields['content']);
        $this->assertContains('process-web-entry', $slugs);
        $this->assertContains('data-connector-creation-rules', $slugs);
        $this->assertContains('data-connector-in-process', $slugs);
        $this->assertContains('collection-creation-rules', $slugs);
        $this->assertContains('collection-test-data', $slugs);
        $this->assertContains('flowgenie-creation-rules', $slugs);

        $flowGenieSkill = collect($skills)->firstWhere('slug', McpSystemSkills::FLOWGENIE_CREATION_RULES_SLUG);
        $this->assertNotNull($flowGenieSkill);
        $this->assertSame(McpGuidance::SCOPE_FLOW_GENIES, $flowGenieSkill['scope']);
        $this->assertStringContainsString('instructions', $flowGenieSkill['content']);
        $this->assertStringContainsString('flow_genie_id', $flowGenieSkill['content']);

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
