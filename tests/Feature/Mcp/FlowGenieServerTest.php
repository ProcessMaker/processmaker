<?php

namespace Tests\Feature\Mcp;

use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use ProcessMaker\Mcp\McpGuidance;
use ProcessMaker\Mcp\Resources\McpSkillResource;
use ProcessMaker\Mcp\Servers\FlowGenieServer;
use ProcessMaker\Mcp\Tools\FlowGenies\CreateFlowGenieTool;
use ProcessMaker\Mcp\Tools\FlowGenies\DeleteFlowGenieTool;
use ProcessMaker\Mcp\Tools\FlowGenies\GetFlowGenieTool;
use ProcessMaker\Mcp\Tools\FlowGenies\ListFlowGenieCategoriesTool;
use ProcessMaker\Mcp\Tools\FlowGenies\ListFlowGeniesTool;
use ProcessMaker\Mcp\Tools\FlowGenies\UpdateFlowGenieTool;
use ProcessMaker\Mcp\Tools\FlowGenies\ValidateFlowGenieTool;
use ProcessMaker\Mcp\Tools\GetMcpGuidanceTool;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\User;
use ProcessMaker\Package\PackageAi\Models\FlowGenie;
use ProcessMaker\Package\PackageAi\Models\FlowGenieCategory;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class FlowGenieServerTest extends TestCase
{
    use RequestHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password' => Hash::make('password'),
            'is_administrator' => true,
        ]);

        if (!class_exists(FlowGenie::class)) {
            $this->markTestSkipped('package-ai is not installed');
        }
    }

    public function testAdminCanCreateListUpdateAndDeleteAFlowGenie(): void
    {
        $category = FlowGenieCategory::query()->create([
            'name' => 'MCP FlowGenie Category',
            'status' => 'ACTIVE',
            'is_system' => false,
        ]);

        $created = FlowGenieServer::actingAs($this->user)->tool(CreateFlowGenieTool::class, [
            'name' => 'MCP Review Assistant',
            'description' => 'Summarize requests and suggest approve or reject',
            'flow_genie_category_id' => (string) $category->id,
            'config' => [
                'prompt' => 'Summarize the request data and suggest APPROVED or REJECTED.',
                'response_format' => 'json',
                'model' => 'gpt-4o-mini',
                'provider' => 'openai',
            ],
        ]);

        $created->assertOk()
            ->assertHasNoErrors()
            ->assertSee('MCP Review Assistant')
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('name', 'MCP Review Assistant')
                ->where('has_instructions', true)
                ->where('output_type', 'JSON')
                ->where('config.instructions', 'Summarize the request data and suggest APPROVED or REJECTED.')
                ->etc());

        $genie = FlowGenie::query()->where('name', 'MCP Review Assistant')->firstOrFail();

        FlowGenieServer::actingAs($this->user)->tool(ListFlowGeniesTool::class, [
            'filter' => 'MCP Review',
        ])->assertOk()->assertSee('MCP Review Assistant');

        FlowGenieServer::actingAs($this->user)->tool(ListFlowGenieCategoriesTool::class, [
            'filter' => 'MCP FlowGenie',
        ])->assertOk()->assertSee('MCP FlowGenie Category');

        FlowGenieServer::actingAs($this->user)->tool(GetFlowGenieTool::class, [
            'id' => $genie->id,
        ])->assertOk()->assertSee('gpt-4o-mini');

        FlowGenieServer::actingAs($this->user)->tool(UpdateFlowGenieTool::class, [
            'id' => $genie->id,
            'description' => 'Updated description for MCP FlowGenie',
            'config' => [
                'instructions' => 'Return only a short JSON decision.',
                'output_type' => 'JSON',
                'model' => 'gpt-4o-mini',
                'provider' => 'openai',
            ],
        ])->assertOk()
            ->assertSee('Updated description for MCP FlowGenie')
            ->assertSee('Return only a short JSON decision.');

        FlowGenieServer::actingAs($this->user)->tool(DeleteFlowGenieTool::class, [
            'id' => $genie->id,
        ])->assertOk()->assertSee('deleted');

        $this->assertDatabaseMissing('flow_genies', ['id' => $genie->id]);
    }

    public function testValidateFlowGenieReturnsNormalizedConfig(): void
    {
        FlowGenieServer::actingAs($this->user)->tool(ValidateFlowGenieTool::class, [
            'config' => [
                'prompt' => 'Hello agent',
                'response_format' => 'text',
            ],
        ])->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('ok', true)
                ->where('normalized.instructions', 'Hello agent')
                ->where('normalized.output_type', 'Text (default)')
                ->etc());
    }

    public function testGuidanceListsFlowGenieCreationSkill(): void
    {
        Setting::updateOrCreate([
            'key' => McpGuidance::SKILLS_KEY,
        ], [
            'format' => 'component',
            'config' => [
                [
                    'id' => 'mcp-system-screen-creation-rules',
                    'name' => 'Screen creation rules',
                    'slug' => 'screen-creation-rules',
                    'when' => 'Before create-screen',
                    'content' => 'Use Vue mounts',
                    'system' => true,
                    'scope' => McpGuidance::SCOPE_SCREENS,
                ],
                [
                    'id' => 'mcp-system-flowgenie-creation-rules',
                    'name' => 'FlowGenie creation rules',
                    'slug' => 'flowgenie-creation-rules',
                    'when' => 'Before create-flow-genie',
                    'content' => 'instructions model output_type',
                    'system' => true,
                    'scope' => McpGuidance::SCOPE_FLOW_GENIES,
                ],
            ],
            'name' => 'Skills',
            'group' => 'MCP',
        ]);

        FlowGenieServer::actingAs($this->user)->tool(GetMcpGuidanceTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->has('skills', 1)
                ->where('skills.0.slug', 'flowgenie-creation-rules')
                ->missing('skills.0.content')
                ->etc());

        $server = app(FlowGenieServer::class, ['transport' => new FakeTransporter()]);
        $resources = $server->createContext()->resources();

        $this->assertCount(1, $resources);
        $this->assertInstanceOf(McpSkillResource::class, $resources->first());
        $this->assertSame('pm://mcp/skills/flowgenie-creation-rules', $resources->first()->uri());
    }
}
