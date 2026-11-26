<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker\Repositories;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Repositories\TokenPersistenceRawRepository;
use Tests\TestCase;

class TokenPersistenceRawRepositoryTest extends TestCase
{
    protected function tearDown(): void
    {
        Config::set('app.token_persistence_raw_enabled', false);
        parent::tearDown();
    }

    public function testSaveActivatedTokenUpdatesRowWithRawSql(): void
    {
        $request = ProcessRequest::factory()->create();
        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
            'process_id' => $request->process_id,
            'status' => 'CLOSED',
            'element_name' => 'Old',
        ]);

        $token->status = 'ACTIVE';
        $token->element_name = 'Updated Task';
        $token->user_id = $request->user_id;

        app(TokenPersistenceRawRepository::class)->saveActivatedToken($token);

        $this->assertDatabaseHas('process_request_tokens', [
            'id' => $token->id,
            'status' => 'ACTIVE',
            'element_name' => 'Updated Task',
        ]);
    }

    public function testPersistInstanceUpdatedMergesDataWithoutEloquentSave(): void
    {
        $request = ProcessRequest::factory()->create([
            'data' => ['existing' => 'value'],
            'execution_revision' => 2,
        ]);
        $request->loadProcessRequestInstance();

        $request->getDataStore()->putData('new_key', 'new_value');
        $request->last_stage_name = 'Review';

        app(TokenPersistenceRawRepository::class)->persistInstanceUpdated($request);

        $request->refresh();

        $this->assertSame('value', $request->data['existing']);
        $this->assertSame('new_value', $request->data['new_key']);
        $this->assertSame(3, (int) $request->execution_revision);
        $this->assertSame('Review', $request->last_stage_name);
    }

    public function testSaveClosedTokenPersistsDataAsJsonObjectNotDoubleEncodedString(): void
    {
        $request = ProcessRequest::factory()->create();
        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
            'process_id' => $request->process_id,
            'status' => 'ACTIVE',
            'data' => [],
        ]);

        $token->status = 'CLOSED';
        $token->data = ['form_input_1' => 'expected value'];
        $token->token_properties = ['data' => ['loop' => true]];

        app(TokenPersistenceRawRepository::class)->saveClosedToken($token);
        $token->refresh();

        $this->assertIsArray($token->data);
        $this->assertSame('expected value', $token->data['form_input_1']);
        $this->assertIsArray($token->token_properties);

        $row = DB::selectOne(
            'SELECT JSON_TYPE(data) AS data_type, JSON_TYPE(token_properties) AS props_type FROM process_request_tokens WHERE id = ?',
            [$token->id]
        );
        $this->assertSame('OBJECT', $row->data_type);
        $this->assertSame('OBJECT', $row->props_type);
    }

    public function testSaveCompletedTokenPersistsTokenPropertiesAsJsonObject(): void
    {
        $request = ProcessRequest::factory()->create();
        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
            'process_id' => $request->process_id,
            'status' => 'ACTIVE',
        ]);

        $token->status = 'COMPLETED';
        $token->completed_at = now();
        $token->token_properties = ['mi_index' => 2];

        app(TokenPersistenceRawRepository::class)->saveCompletedToken($token);
        $token->refresh();

        $this->assertIsArray($token->token_properties);
        $this->assertSame(2, $token->token_properties['mi_index']);

        $row = DB::selectOne(
            'SELECT JSON_TYPE(token_properties) AS props_type FROM process_request_tokens WHERE id = ?',
            [$token->id]
        );
        $this->assertSame('OBJECT', $row->props_type);
    }

    public function testSaveActivatedTokenPersistsSelfServiceGroupsAndTokenPropertiesAsJsonObjects(): void
    {
        $request = ProcessRequest::factory()->create();
        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
            'process_id' => $request->process_id,
            'status' => 'CLOSED',
            'is_self_service' => true,
        ]);

        $token->status = 'ACTIVE';
        $token->self_service_groups = ['groups' => [10, 20], 'users' => [30]];
        $token->token_properties = ['allow_reassignment' => true];

        app(TokenPersistenceRawRepository::class)->saveActivatedToken($token);
        $token->refresh();

        $this->assertIsArray($token->self_service_groups);
        $this->assertSame([10, 20], $token->self_service_groups['groups']);
        $this->assertIsArray($token->token_properties);
        $this->assertTrue($token->token_properties['allow_reassignment']);

        $row = DB::selectOne(
            'SELECT JSON_TYPE(self_service_groups) AS groups_type, JSON_TYPE(token_properties) AS props_type FROM process_request_tokens WHERE id = ?',
            [$token->id]
        );
        $this->assertSame('OBJECT', $row->groups_type);
        $this->assertSame('OBJECT', $row->props_type);
    }

    public function testPersistInstanceUpdatedPersistsRequestDataAsJsonObject(): void
    {
        $request = ProcessRequest::factory()->create([
            'data' => ['existing' => 'value'],
            'execution_revision' => 0,
        ]);
        $request->loadProcessRequestInstance();
        $request->getDataStore()->putData('added', 'from_store');

        app(TokenPersistenceRawRepository::class)->persistInstanceUpdated($request);

        $row = DB::selectOne(
            'SELECT JSON_TYPE(data) AS data_type FROM process_requests WHERE id = ?',
            [$request->id]
        );
        $this->assertSame('OBJECT', $row->data_type);

        $request->refresh();
        $this->assertIsArray($request->data);
        $this->assertSame('value', $request->data['existing']);
        $this->assertSame('from_store', $request->data['added']);
    }
}
