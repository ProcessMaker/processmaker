<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker\Repositories;

use Illuminate\Support\Facades\Config;
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
}
