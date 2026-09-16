<?php

namespace Tests\Unit\Mcp\Process;

use ProcessMaker\Mcp\Migration\Writer;
use ProcessMaker\Mcp\Process\Analyzer;
use ProcessMaker\Mcp\Process\BpmnDesigner;
use ProcessMaker\Mcp\Process\Designer;
use ProcessMaker\Mcp\Process\Reader;
use ProcessMaker\Mcp\Process\ScreenBuilder;
use ProcessMaker\Mcp\Process\ScreenControlCatalog;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ProcessAgentTest extends TestCase
{
    use RequestHelper;

    public function testReaderListsProcesses(): void
    {
        $this->actingAs($this->user);
        Process::factory()->create(['user_id' => $this->user->id, 'name' => 'Agent Test Process']);

        $result = app(Reader::class)->listProcesses(filterName: 'Agent Test');

        $this->assertGreaterThanOrEqual(1, $result['total']);
        $this->assertStringContainsString('Agent Test', $result['processes'][0]['name']);
    }

    public function testAnalyzerFindsTasksWithoutScreen(): void
    {
        $this->actingAs($this->user);

        $process = app(Writer::class)->createProcess(['name' => 'Analyze Me']);
        $analysis = app(Analyzer::class)->analyze((string) $process->id);

        $this->assertSame($process->id, $analysis['process_id']);
        $this->assertArrayHasKey('findings', $analysis);
        $this->assertArrayHasKey('suggestions', $analysis);
    }

    public function testApplyProcessDesignCreatesProcessWithScreen(): void
    {
        $this->actingAs($this->user);

        $result = app(Designer::class)->applyDesignPlan([
            'process' => ['name' => 'Designed Via MCP', 'description' => 'Agent design'],
            'bpmn_template' => 'SingleTask',
            'screens' => [[
                'title' => 'Request Form',
                'fields' => [[
                    'variable' => 'request_title',
                    'label' => 'Title',
                    'type' => 'text',
                    'required' => true,
                ]],
            ]],
            'variables' => [[
                'var_name' => 'request_title',
                'var_default' => '',
            ]],
        ]);

        $this->assertNotEmpty($result['process_id']);
        $this->assertCount(1, $result['screens']);
        $this->assertNotEmpty($result['linked_screens']);
        $this->assertTrue($result['validation']['valid']);
    }

    public function testScreenControlCatalogHasRequiredComponents(): void
    {
        $this->assertSame([], ScreenControlCatalog::missingComponents());
        $report = ScreenControlCatalog::catalogReport();
        $this->assertArrayHasKey('FormInput', $report);
        $this->assertArrayHasKey('FormButton', $report);
        $this->assertGreaterThan(0, $report['FormInput']['inspector_fields']);
    }

    public function testScreenBuilderCreatesFormInputConfig(): void
    {
        $builder = app(ScreenBuilder::class);
        $config = $builder->buildFormConfig([[
            'variable' => 'customer_name',
            'label' => 'Customer',
            'type' => 'text',
        ]], 'Customer Form');

        $this->assertSame('Customer Form', $config[0]['name']);
        $this->assertSame('FormInput', $config[0]['items'][0]['component']);
        $this->assertSame('customer_name', $config[0]['items'][0]['config']['name']);
        $this->assertSame('string', $config[0]['items'][0]['config']['dataFormat']);
        $this->assertNotEmpty($config[0]['items'][0]['uuid']);
        $this->assertNotEmpty($config[0]['items'][0]['inspector']);
        $this->assertSame('FormInput', $config[0]['items'][0]['editor-component']);
        $this->assertSame([], $config[0]['computed']);
        $this->assertSame('FormButton', $config[0]['items'][1]['component']);
        $this->assertTrue($config[0]['items'][1]['config']['defaultSubmit']);

        $validation = $builder->validateScreenConfig($config);
        $this->assertTrue($validation['valid']);
    }

    public function testAnalyzerFlagsScreenWithoutSubmit(): void
    {
        $builder = app(ScreenBuilder::class);
        $issues = $builder->validateScreenConfig([[
            'name' => 'Broken',
            'computed' => [],
            'items' => [[
                'uuid' => '00000000-0000-0000-0000-000000000001',
                'label' => 'Name',
                'component' => 'FormInput',
                'config' => ['name' => 'name', 'label' => 'Name'],
            ]],
        ]]);

        $this->assertFalse($issues['valid']);
        $codes = array_column($issues['issues'], 'code');
        $this->assertContains('missing_submit_button', $codes);
        $this->assertContains('field_missing_inspector', $codes);
    }

    public function testWriterNormalizesBrokenAgentScreenConfig(): void
    {
        $this->actingAs($this->user);

        $result = app(Writer::class)->createScreenResult([
            'title' => 'Broken Agent Screen',
            'description' => 'Should be normalized',
            'config_json' => [[
                'name' => 'data',
                'items' => [[
                    'name' => 'pet_name',
                    'label' => 'Pet Name',
                    'component' => 'FormInput',
                    'config' => [
                        'name' => 'pet_name',
                        'type' => 'text',
                        'label' => 'Pet Name',
                        'validation' => 'required',
                    ],
                ], [
                    'name' => 'pet_species',
                    'label' => 'Species',
                    'component' => 'FormSelectList',
                    'config' => [
                        'name' => 'pet_species',
                        'label' => 'Species',
                        'validation' => 'required',
                    ],
                ]],
            ]],
        ]);

        $item = $result->screen->config[0]['items'][0];
        $this->assertNotEmpty($item['uuid']);
        $this->assertNotEmpty($item['inspector']);
        $this->assertSame('FormInput', $item['editor-component']);
        $this->assertSame('FormSelect', $result->screen->config[0]['items'][1]['component']);
        $this->assertSame('FormButton', $result->screen->config[0]['items'][2]['component']);
        $this->assertSame([], $result->screen->config[0]['computed']);
    }

    public function testPlatformWriterCreatesScreenViaImport(): void
    {
        $this->actingAs($this->user);

        $config = app(ScreenBuilder::class)->buildFormConfig([[
            'variable' => 'platform_field',
            'label' => 'Platform Field',
            'type' => 'text',
        ]], 'Platform Screen');

        $result = app(\ProcessMaker\Mcp\Platform\PlatformWriter::class)->createScreenResult([
            'title' => 'Platform Import Screen',
            'description' => 'Created through ImportScreen',
            'config_json' => $config,
        ]);

        $this->assertNotEmpty($result->screen->id);
        $this->assertSame('Platform Import Screen', $result->screen->title);
        $this->assertSame('import_screen', $result->importMethod);
        $this->assertSame([], $result->warnings);
        $this->assertSame('FormInput', $result->screen->config[0]['items'][0]['component']);
        $this->assertNotEmpty($result->screen->config[0]['items'][0]['inspector']);
    }

    public function testCreateProcessAssignsCategoryAndStartPermissions(): void
    {
        $this->actingAs($this->user);

        $bpmn = file_get_contents(database_path('processes/templates/SingleTask.bpmn'));
        $process = app(Writer::class)->createProcess([
            'name' => 'Startable MCP Process',
            'bpmn' => $bpmn,
        ]);

        $this->assertGreaterThan(0, $process->categories()->count());
        $this->assertGreaterThan(0, $process->usersCanStart('StartEventUID')->count());
        $this->assertSame(1, Process::nonSystem()->active()->categoryStatus('ACTIVE')->where('id', $process->id)->count());
    }

    public function testBpmnDesignerAddsUserTaskOnSingleTaskTemplate(): void
    {
        $this->actingAs($this->user);

        $bpmn = file_get_contents(database_path('processes/templates/SingleTask.bpmn'));
        $process = app(Writer::class)->createProcess([
            'name' => 'BPMN Design',
            'bpmn' => $bpmn,
        ]);

        $added = app(BpmnDesigner::class)->addUserTask((string) $process->id, 'Review Step');
        $inventory = app(Reader::class)->getProcessInventory((string) $process->id);

        $this->assertNotEmpty($added['element_id']);
        $this->assertSame(2, $inventory['counts']['tasks']);
        $this->assertTrue(app(Writer::class)->validateProcess((string) $process->id)['valid']);
    }
}
