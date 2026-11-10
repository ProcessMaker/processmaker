<?php

namespace Tests\Feature\Mcp;

use Illuminate\Support\Facades\Event;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use ProcessMaker\Events\ScreenCreated;
use ProcessMaker\Events\ScreenDeleted;
use ProcessMaker\Events\ScreenUpdated;
use ProcessMaker\Mcp\McpGuidance;
use ProcessMaker\Mcp\Resources\McpSkillResource;
use ProcessMaker\Mcp\Servers\ScreenServer;
use ProcessMaker\Mcp\Tools\GetMcpGuidanceTool;
use ProcessMaker\Mcp\Tools\GetMcpSkillTool;
use ProcessMaker\Mcp\Tools\Screens\CreateScreenTool;
use ProcessMaker\Mcp\Tools\Screens\DeleteScreenTool;
use ProcessMaker\Mcp\Tools\Screens\GetScreenTool;
use ProcessMaker\Mcp\Tools\Screens\ListScreensTool;
use ProcessMaker\Mcp\Tools\Screens\UpdateScreenTool;
use ProcessMaker\Mcp\Tools\Screens\ValidateScreenTool;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ScreenServerTest extends TestCase
{
    use RequestHelper;

    public function testAdminCanCreateListUpdateAndDeleteAScreen(): void
    {
        Event::fake([
            ScreenCreated::class,
            ScreenUpdated::class,
            ScreenDeleted::class,
        ]);

        $category = ScreenCategory::factory()->create();
        $created = ScreenServer::actingAs($this->user)->tool(CreateScreenTool::class, [
            'title' => 'Intake Form',
            'description' => 'Collects intake details',
            'type' => 'FORM',
            'screen_category_id' => $category->id,
            'config' => [
                ['name' => 'Intake'],
            ],
        ]);

        $created->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Intake Form')
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('title', 'Intake Form')
                ->where('type', 'FORM')
                ->where('config.0.name', 'Intake')
                ->etc());

        $screen = Screen::where('title', 'Intake Form')->firstOrFail();
        Event::assertDispatched(ScreenCreated::class);

        ScreenServer::actingAs($this->user)->tool(ListScreensTool::class, [
            'filter' => 'Intake',
        ])->assertOk()->assertSee('Intake Form');

        ScreenServer::actingAs($this->user)->tool(GetScreenTool::class, [
            'id' => $screen->id,
        ])->assertOk()->assertSee('Collects intake details');

        ScreenServer::actingAs($this->user)->tool(UpdateScreenTool::class, [
            'id' => $screen->id,
            'description' => 'Updated intake details',
        ])->assertOk()->assertSee('Updated intake details');

        $this->assertSame('Updated intake details', $screen->refresh()->description);
        Event::assertDispatched(ScreenUpdated::class);

        ScreenServer::actingAs($this->user)->tool(DeleteScreenTool::class, [
            'id' => $screen->id,
        ])->assertOk()->assertStructuredContent([
            'id' => $screen->id,
            'deleted' => true,
        ]);

        $this->assertNull(Screen::find($screen->id));
        Event::assertDispatched(ScreenDeleted::class);
    }

    public function testScreenToolsRequireAnAuthenticatedUserWithPermission(): void
    {
        ScreenServer::tool(ListScreensTool::class)
            ->assertHasErrors(['Authenticate with a ProcessMaker API token']);

        $member = User::factory()->create([
            'is_administrator' => false,
        ]);

        ScreenServer::actingAs($member)->tool(ListScreensTool::class)
            ->assertHasErrors(['view-screens']);
    }

    public function testDefaultScreensCannotBeDeleted(): void
    {
        $screen = Screen::factory()->create([
            'is_default' => 1,
        ]);

        ScreenServer::actingAs($this->user)->tool(DeleteScreenTool::class, [
            'id' => $screen->id,
        ])->assertHasErrors(['Default screens cannot be deleted.']);

        $this->assertNotNull(Screen::find($screen->id));
    }

    public function testCreateScreenNormalizesMissingInspectorAndItems(): void
    {
        Event::fake([ScreenCreated::class]);

        $category = ScreenCategory::factory()->create();
        ScreenServer::actingAs($this->user)->tool(CreateScreenTool::class, [
            'title' => 'MCP Safe Config',
            'description' => 'Ensures inspector defaults',
            'type' => 'FORM',
            'screen_category_id' => $category->id,
            'config' => [
                [
                    'name' => 'Page 1',
                    'items' => [
                        [
                            'component' => 'FormInput',
                            'label' => 'Name',
                            'config' => ['name' => 'name', 'label' => 'Name'],
                        ],
                    ],
                ],
            ],
            'computed' => null,
            'watchers' => null,
        ])->assertOk()->assertHasNoErrors();

        $screen = Screen::where('title', 'MCP Safe Config')->firstOrFail();
        $this->assertSame([], $screen->config[0]['items'][0]['inspector']);
        $this->assertSame([], $screen->computed);
        $this->assertSame([], $screen->watchers);
    }

    public function testCreateScreenRejectsSelectListWithoutOptionsList(): void
    {
        $category = ScreenCategory::factory()->create();

        ScreenServer::actingAs($this->user)->tool(CreateScreenTool::class, [
            'title' => 'Broken Select Screen',
            'description' => 'Select missing optionsList',
            'type' => 'FORM',
            'screen_category_id' => $category->id,
            'config' => [
                [
                    'name' => 'Page 1',
                    'items' => [
                        [
                            'component' => 'FormSelectList',
                            'inspector' => [],
                            'config' => [
                                'name' => 'category',
                                'label' => 'Category',
                                'validation' => 'required',
                                'options' => [
                                    'dataSource' => 'provideData',
                                    'jsonData' => '[{"value":"Travel","content":"Travel"}]',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ])->assertHasErrors(['optionsList']);
    }

    public function testValidateScreenReportsSelectAndVueIssues(): void
    {
        ScreenServer::actingAs($this->user)->tool(ValidateScreenTool::class, [
            'type' => 'FORM',
            'config' => [
                [
                    'name' => 'Page 1',
                    'items' => [
                        [
                            'component' => 'FormSelectList',
                            'inspector' => [],
                            'config' => [
                                'name' => 'category',
                                'options' => [
                                    'dataSource' => 'provideData',
                                ],
                            ],
                        ],
                        [
                            'component' => 'VueComponentRenderTemplate',
                            'inspector' => [],
                            'config' => [
                                'contentVue' => '',
                                'components' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ])->assertOk()->assertStructuredContent(fn (AssertableJson $json) => $json
            ->where('ok', false)
            ->has('errors')
            ->etc());
    }

    public function testHttpServerRequiresABearerToken(): void
    {
        $this->postJson('/mcp/screens', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [],
        ])->assertUnauthorized();
    }

    public function testGetMcpGuidanceReturnsCatalogWithoutSkillBodies(): void
    {
        $this->seedMcpGuidance();

        ScreenServer::actingAs($this->user)
            ->tool(GetMcpGuidanceTool::class)
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee([
                'Always use FormInput for customer names',
                'Screen Styles',
                'Before create-screen when styling matters',
            ])
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('system_prompt', 'Always use FormInput for customer names')
                ->where('skills.0.name', 'Screen Styles')
                ->where('skills.0.slug', 'screen-styles')
                ->where('skills.0.when', 'Before create-screen when styling matters')
                ->where('skills.0.uri', 'pm://mcp/skills/screen-styles')
                ->missing('skills.0.content')
                ->etc());
    }

    public function testGetMcpSkillReturnsFullSkillBodyBySlug(): void
    {
        $this->seedMcpGuidance();

        ScreenServer::actingAs($this->user)
            ->tool(GetMcpSkillTool::class, ['slug' => 'screen-styles'])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Prefer primary buttons')
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('slug', 'screen-styles')
                ->where('when', 'Before create-screen when styling matters')
                ->where('content', 'Prefer primary buttons')
                ->where('uri', 'pm://mcp/skills/screen-styles')
                ->etc());

        ScreenServer::actingAs($this->user)
            ->tool(GetMcpSkillTool::class, ['slug' => 'missing-skill'])
            ->assertHasErrors(['Skill not found for slug: missing-skill']);
    }

    public function testServerInstructionsIncludeCatalogWithoutSkillBodies(): void
    {
        $this->seedMcpGuidance();

        $server = app(ScreenServer::class, ['transport' => new FakeTransporter()]);
        $instructions = $server->createContext()->instructions;

        $this->assertStringContainsString('get-mcp-guidance', $instructions);
        $this->assertStringContainsString('get-mcp-skill', $instructions);
        $this->assertStringContainsString('Always use FormInput for customer names', $instructions);
        $this->assertStringContainsString('Skills catalog', $instructions);
        $this->assertStringContainsString('Screen Styles', $instructions);
        $this->assertStringContainsString('Before create-screen when styling matters', $instructions);
        $this->assertStringContainsString('pm://mcp/skills/screen-styles', $instructions);
        $this->assertStringNotContainsString('Prefer primary buttons', $instructions);
    }

    public function testSkillResourcesAreRegisteredFromSettings(): void
    {
        $this->seedMcpGuidance();

        $server = app(ScreenServer::class, ['transport' => new FakeTransporter()]);
        $resources = $server->createContext()->resources();

        $this->assertCount(1, $resources);
        $this->assertInstanceOf(McpSkillResource::class, $resources->first());
        $this->assertSame('pm://mcp/skills/screen-styles', $resources->first()->uri());

        ScreenServer::actingAs($this->user)
            ->resource(new McpSkillResource(
                'Screen Styles',
                'screen-styles',
                'Prefer primary buttons',
                'Before create-screen when styling matters',
            ))
            ->assertOk()
            ->assertSee('Prefer primary buttons');
    }

    private function seedMcpGuidance(): void
    {
        Setting::factory()->create([
            'key' => McpGuidance::SYSTEM_PROMPT_KEY,
            'format' => 'textarea',
            'config' => 'Always use FormInput for customer names',
            'name' => 'System Prompt',
            'group' => 'MCP',
        ]);

        Setting::factory()->create([
            'key' => McpGuidance::SKILLS_KEY,
            'format' => 'component',
            'config' => [
                [
                    'id' => 'skill-1',
                    'name' => 'Screen Styles',
                    'slug' => 'screen-styles',
                    'when' => 'Before create-screen when styling matters',
                    'content' => 'Prefer primary buttons',
                ],
            ],
            'name' => 'Skills',
            'group' => 'MCP',
            'ui' => ['component' => 'McpSkills'],
        ]);
    }
}
