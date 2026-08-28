<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1_1;

use Illuminate\Support\Facades\Config;
use Mockery;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class TaskControllerUpdateTest extends TestCase
{
    use RequestHelper;

    protected function tearDown(): void
    {
        Config::set('app.task_update_v1_1_enabled', false);
        Config::set('app.token_persistence_raw_enabled', false);
        Mockery::close();
        parent::tearDown();
    }

    public function testUpdateReturnsNotFoundWhenFeatureDisabled(): void
    {
        Config::set('app.task_update_v1_1_enabled', false);

        $task = ProcessRequestToken::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'ACTIVE',
        ]);

        $response = $this->apiCall('PUT', route('api.1.1.tasks.update', $task->id), [
            'status' => 'COMPLETED',
            'data' => ['foo' => 'bar'],
        ]);

        $response->assertStatus(404);
        $response->assertJsonFragment([
            'message' => 'Task update API v1.1 is disabled. Use PUT /api/1.0/tasks/{id} instead.',
        ]);
    }

    public function testUpdateRejectsNonCompletionStatus(): void
    {
        Config::set('app.task_update_v1_1_enabled', true);

        $task = ProcessRequestToken::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'ACTIVE',
        ]);

        $response = $this->apiCall('PUT', route('api.1.1.tasks.update', $task->id), [
            'user_id' => User::factory()->create()->id,
        ]);

        $response->assertStatus(422);
    }

    public function testUpdateCompletesTaskWhenEnabled(): void
    {
        Config::set('app.task_update_v1_1_enabled', true);
        Config::set('app.token_persistence_raw_enabled', true);

        $request = ProcessRequest::factory()->create();
        $task = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
            'process_id' => $request->process_id,
            'user_id' => $this->user->id,
            'status' => 'ACTIVE',
        ]);

        WorkflowManager::shouldReceive('completeTask')
            ->once()
            ->with(Mockery::any(), Mockery::any(), Mockery::any(), ['foo' => 'bar']);

        $response = $this->apiCall('PUT', route('api.1.1.tasks.update', $task->id), [
            'status' => 'COMPLETED',
            'data' => ['foo' => 'bar'],
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $task->id,
            'status' => $task->status,
        ]);
    }

    public function testUpdateDeniesUnauthorizedUser(): void
    {
        Config::set('app.task_update_v1_1_enabled', true);

        $caller = User::factory()->create(['is_administrator' => false]);
        $assignee = User::factory()->create(['is_administrator' => false]);
        $task = ProcessRequestToken::factory()->create([
            'user_id' => $assignee->id,
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($caller, 'api')->json(
            'PUT',
            '/api/' . preg_replace('/^.*\/api\//i', '', route('api.1.1.tasks.update', $task->id)),
            [
                'status' => 'COMPLETED',
                'data' => ['foo' => 'bar'],
            ]
        );

        $response->assertStatus(403);
    }
}
