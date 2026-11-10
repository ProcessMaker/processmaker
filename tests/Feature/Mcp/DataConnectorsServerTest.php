<?php

namespace Tests\Feature\Mcp;

use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use ProcessMaker\Mcp\McpGuidance;
use ProcessMaker\Mcp\Resources\McpSkillResource;
use ProcessMaker\Mcp\Servers\DataConnectorsServer;
use ProcessMaker\Mcp\Tools\Collections\CreateCollectionTool;
use ProcessMaker\Mcp\Tools\Collections\GetCollectionTool;
use ProcessMaker\Mcp\Tools\Collections\ListCollectionsTool;
use ProcessMaker\Mcp\Tools\Collections\UpdateCollectionTool;
use ProcessMaker\Mcp\Tools\DataConnectors\CreateDataConnectorTool;
use ProcessMaker\Mcp\Tools\DataConnectors\GetDataConnectorTool;
use ProcessMaker\Mcp\Tools\DataConnectors\ListDataConnectorCategoriesTool;
use ProcessMaker\Mcp\Tools\DataConnectors\ListDataConnectorsTool;
use ProcessMaker\Mcp\Tools\DataConnectors\UpdateDataConnectorTool;
use ProcessMaker\Mcp\Tools\GetMcpGuidanceTool;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\User;
use ProcessMaker\PackageHelper;
use ProcessMaker\Packages\Connectors\DataSources\Models\DataSource;
use ProcessMaker\Packages\Connectors\DataSources\Models\DataSourceCategory;
use ProcessMaker\Plugins\Collections\Models\Collection;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class DataConnectorsServerTest extends TestCase
{
    use RequestHelper;

    protected function setUp(): void
    {
        parent::setUp();

        // RequestHelper::setUp is shadowed when this class defines setUp — recreate admin user.
        $this->user = User::factory()->create([
            'password' => Hash::make('password'),
            'is_administrator' => true,
        ]);

        if (!PackageHelper::isPackageInstalled(PackageHelper::PM_PACKAGE_DATA_SOURCES)) {
            $this->markTestSkipped('package-data-sources is not installed');
        }
        if (!PackageHelper::isPackageInstalled(PackageHelper::PM_PACKAGE_COLLECTIONS)) {
            $this->markTestSkipped('package-collections is not installed');
        }
    }

    public function testAdminCanCreateListAndUpdateADataConnector(): void
    {
        $category = DataSourceCategory::factory()->create([
            'status' => 'ACTIVE',
            'is_system' => false,
        ]);

        $created = DataConnectorsServer::actingAs($this->user)->tool(CreateDataConnectorTool::class, [
            'name' => 'MCP Demo API',
            'description' => 'Demo connector',
            'type' => 'REST',
            'authtype' => 'NONE',
            'data_source_category_id' => (string) $category->id,
            'api_base_url' => 'https://example.test',
            'endpoints' => [
                'listItems' => [
                    'purpose' => 'listItems',
                    'method' => 'GET',
                    'url' => '{{__api_base_url__}}/items',
                ],
            ],
        ]);

        $created->assertOk()
            ->assertHasNoErrors()
            ->assertSee('MCP Demo API')
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('name', 'MCP Demo API')
                ->where('type', 'REST')
                ->where('authtype', 'NONE')
                ->has('endpoints.listItems')
                ->etc());

        $connector = DataSource::where('name', 'MCP Demo API')->firstOrFail();

        DataConnectorsServer::actingAs($this->user)->tool(ListDataConnectorsTool::class, [
            'filter' => 'MCP Demo',
        ])->assertOk()->assertSee('MCP Demo API');

        DataConnectorsServer::actingAs($this->user)->tool(ListDataConnectorCategoriesTool::class, [
            'filter' => $category->name,
        ])->assertOk()->assertSee($category->name);

        DataConnectorsServer::actingAs($this->user)->tool(GetDataConnectorTool::class, [
            'id' => $connector->id,
        ])->assertOk()->assertSee('listItems');

        DataConnectorsServer::actingAs($this->user)->tool(UpdateDataConnectorTool::class, [
            'id' => $connector->id,
            'description' => 'Updated demo connector',
        ])->assertOk()->assertSee('Updated demo connector');

        $this->assertSame('Updated demo connector', $connector->refresh()->description);
    }

    public function testAdminCanCreateListAndUpdateACollection(): void
    {
        ProcessCategory::factory()->create([
            'name' => 'System',
            'status' => 'ACTIVE',
            'is_system' => true,
        ]);

        $createScreen = Screen::factory()->create(['title' => 'MCP Create', 'type' => 'FORM', 'config' => [['items' => []]]]);
        $readScreen = Screen::factory()->create(['title' => 'MCP Read', 'type' => 'DISPLAY', 'config' => [['items' => []]]]);
        $updateScreen = Screen::factory()->create(['title' => 'MCP Update', 'type' => 'FORM', 'config' => [['items' => []]]]);

        $created = DataConnectorsServer::actingAs($this->user)->tool(CreateCollectionTool::class, [
            'name' => 'MCP Demo Collection',
            'description' => 'Collection for MCP tests',
            'create_screen_id' => $createScreen->id,
            'read_screen_id' => $readScreen->id,
            'update_screen_id' => $updateScreen->id,
        ]);

        $created->assertOk()
            ->assertHasNoErrors()
            ->assertSee('MCP Demo Collection');

        $collection = Collection::where('name', 'MCP Demo Collection')->firstOrFail();

        DataConnectorsServer::actingAs($this->user)->tool(ListCollectionsTool::class, [
            'filter' => 'MCP Demo Collection',
        ])->assertOk()->assertSee('MCP Demo Collection');

        DataConnectorsServer::actingAs($this->user)->tool(GetCollectionTool::class, [
            'id' => $collection->id,
        ])->assertOk()->assertSee('Collection for MCP tests');

        DataConnectorsServer::actingAs($this->user)->tool(UpdateCollectionTool::class, [
            'id' => $collection->id,
            'description' => 'Updated collection description',
        ])->assertOk()->assertSee('Updated collection description');
    }

    public function testDataConnectorToolsRequirePermission(): void
    {
        DataConnectorsServer::tool(ListDataConnectorsTool::class)
            ->assertHasErrors(['Authenticate with a ProcessMaker API token']);

        $member = User::factory()->create([
            'is_administrator' => false,
        ]);

        DataConnectorsServer::actingAs($member)->tool(ListDataConnectorsTool::class)
            ->assertHasErrors(['view-data-sources']);
    }

    public function testCreateRejectsEndpointWithoutPurposeKeyMatch(): void
    {
        $category = DataSourceCategory::factory()->create([
            'status' => 'ACTIVE',
            'is_system' => false,
        ]);

        DataConnectorsServer::actingAs($this->user)->tool(CreateDataConnectorTool::class, [
            'name' => 'Bad Endpoint Map',
            'type' => 'REST',
            'authtype' => 'NONE',
            'data_source_category_id' => (string) $category->id,
            'endpoints' => [
                'wrongKey' => [
                    'purpose' => 'listItems',
                    'method' => 'GET',
                    'url' => 'https://example.test/items',
                ],
            ],
        ])->assertHasErrors(['map key must equal purpose']);
    }

    public function testServerInstructionsIncludeDataConnectorCatalogWithoutSkillBodies(): void
    {
        $this->seedMcpGuidance();

        $server = app(DataConnectorsServer::class, ['transport' => new FakeTransporter()]);
        $instructions = $server->createContext()->instructions;

        $this->assertStringContainsString('get-mcp-guidance', $instructions);
        $this->assertStringContainsString('create-data-connector', $instructions);
        $this->assertStringContainsString('Prefer clear connector names', $instructions);
        $this->assertStringContainsString('data-connector-creation-rules', $instructions);
        $this->assertStringNotContainsString('Author connectors carefully', $instructions);
        $this->assertStringNotContainsString('screen-creation-rules', $instructions);
        $this->assertStringNotContainsString('process-creation-rules', $instructions);
    }

    public function testSkillResourcesAreDataConnectorScoped(): void
    {
        $this->seedMcpGuidance();

        $server = app(DataConnectorsServer::class, ['transport' => new FakeTransporter()]);
        $resources = $server->createContext()->resources();

        $this->assertCount(1, $resources);
        $this->assertInstanceOf(McpSkillResource::class, $resources->first());
        $this->assertSame('pm://mcp/skills/data-connector-creation-rules', $resources->first()->uri());
    }

    public function testGetMcpGuidanceReturnsScopedCatalog(): void
    {
        $this->seedMcpGuidance();

        DataConnectorsServer::actingAs($this->user)
            ->tool(GetMcpGuidanceTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('system_prompt', 'Prefer clear connector names')
                ->has('skills', 1)
                ->where('skills.0.slug', 'data-connector-creation-rules')
                ->missing('skills.0.content')
                ->etc());
    }

    private function seedMcpGuidance(): void
    {
        Setting::updateOrCreate(['key' => McpGuidance::SYSTEM_PROMPT_KEY], [
            'format' => 'textarea',
            'config' => 'Prefer clear connector names',
            'name' => 'System Prompt',
            'group' => 'MCP',
        ]);

        Setting::updateOrCreate(['key' => McpGuidance::SKILLS_KEY], [
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
                    'id' => 'mcp-system-process-creation-rules',
                    'name' => 'Process creation rules',
                    'slug' => 'process-creation-rules',
                    'when' => 'Before create-process',
                    'content' => 'Author BPMN carefully',
                    'system' => true,
                    'scope' => McpGuidance::SCOPE_PROCESSES,
                ],
                [
                    'id' => 'mcp-system-data-connector-creation-rules',
                    'name' => 'Data Connector creation rules',
                    'slug' => 'data-connector-creation-rules',
                    'when' => 'Before create-data-connector',
                    'content' => 'Author connectors carefully',
                    'system' => true,
                    'scope' => McpGuidance::SCOPE_DATA_CONNECTORS,
                ],
            ],
            'name' => 'Skills',
            'group' => 'MCP',
            'ui' => ['component' => 'McpSkills'],
        ]);
    }
}
