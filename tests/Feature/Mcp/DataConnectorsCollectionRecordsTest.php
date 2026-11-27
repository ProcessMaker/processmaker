<?php

namespace Tests\Feature\Mcp;

use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;
use ProcessMaker\Mcp\Servers\DataConnectorsServer;
use ProcessMaker\Mcp\Tools\Collections\CreateCollectionRecordsTool;
use ProcessMaker\Mcp\Tools\Collections\CreateCollectionTool;
use ProcessMaker\Mcp\Tools\Collections\ListCollectionRecordsTool;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use ProcessMaker\PackageHelper;
use ProcessMaker\Plugins\Collections\Models\Collection;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * CollectionObserver DDL + record inserts conflict with RefreshDatabase transactions (MySQL 1412).
 */
class DataConnectorsCollectionRecordsTest extends TestCase
{
    use RequestHelper;

    /**
     * @var list<string>
     */
    protected $connectionsToTransact = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password' => Hash::make('password'),
            'is_administrator' => true,
        ]);

        if (!PackageHelper::isPackageInstalled(PackageHelper::PM_PACKAGE_COLLECTIONS)) {
            $this->markTestSkipped('package-collections is not installed');
        }
    }

    public function testAdminCanSeedCollectionRecords(): void
    {
        ProcessCategory::updateOrCreate(
            ['name' => 'System'],
            ['status' => 'ACTIVE', 'is_system' => true],
        );

        $createScreen = Screen::factory()->create(['title' => 'MCP Rec Create', 'type' => 'FORM', 'config' => [['items' => []]]]);
        $readScreen = Screen::factory()->create(['title' => 'MCP Rec Read', 'type' => 'DISPLAY', 'config' => [['items' => []]]]);
        $updateScreen = Screen::factory()->create(['title' => 'MCP Rec Update', 'type' => 'FORM', 'config' => [['items' => []]]]);

        $created = DataConnectorsServer::actingAs($this->user)->tool(CreateCollectionTool::class, [
            'name' => 'MCP Records Collection',
            'description' => 'For record seeding',
            'create_screen_id' => $createScreen->id,
            'read_screen_id' => $readScreen->id,
            'update_screen_id' => $updateScreen->id,
        ]);
        $created->assertOk()->assertHasNoErrors()->assertSee('MCP Records Collection');

        $collectionId = Collection::where('name', 'MCP Records Collection')->value('id');
        $this->assertNotNull($collectionId);

        DataConnectorsServer::actingAs($this->user)->tool(CreateCollectionRecordsTool::class, [
            'collection_id' => $collectionId,
            'records' => [
                ['first_name' => 'Ada', 'last_name' => 'Lovelace'],
                ['first_name' => 'Grace', 'last_name' => 'Hopper'],
            ],
        ])->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('created_count', 2)
                ->has('data', 2)
                ->etc());

        DataConnectorsServer::actingAs($this->user)->tool(ListCollectionRecordsTool::class, [
            'collection_id' => $collectionId,
        ])->assertOk()->assertSee('Ada');
    }
}
