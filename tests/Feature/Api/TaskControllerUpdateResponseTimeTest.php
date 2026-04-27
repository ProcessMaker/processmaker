<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Notification;
use Mockery;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Response-time assertions for TaskController::update (COMPLETED and reassign paths).
 *
 * Set TASK_UPDATE_MAX_RESPONSE_MS in the environment (milliseconds) to tighten or relax the limit.
 * Default is 20000 ms so CI stays stable; use a lower value locally to catch regressions.
 *
 * @group process_tests
 * @group task_update_timing
 */
class TaskControllerUpdateResponseTimeTest extends TestCase
{
    use RequestHelper;

    private function maxResponseTimeMsForTaskUpdate(): int
    {
        $env = getenv('TASK_UPDATE_MAX_RESPONSE_MS');
        if ($env !== false && $env !== '' && is_numeric($env)) {
            return (int) $env;
        }

        return 20000;
    }

    private function assertResponseWithinMs(float $elapsedMs, int $maxMs, string $label): void
    {
        $this->assertLessThanOrEqual(
            $maxMs,
            $elapsedMs,
            "{$label}: request took " . round($elapsedMs) . "ms, limit {$maxMs}ms (set TASK_UPDATE_MAX_RESPONSE_MS to adjust)"
        );
    }

    public function testCompleteTaskUpdateResponseTime(): void
    {
        $maxMs = $this->maxResponseTimeMsForTaskUpdate();

        $token = ProcessRequestToken::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'ACTIVE',
        ]);

        WorkflowManager::shouldReceive('completeTask')
            ->once()
            ->with(Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any());

        $params = ['status' => 'COMPLETED', 'data' => ['foo' => 'bar']];

        $started = microtime(true);
        $response = $this->apiCall('PUT', '/tasks/' . $token->id, $params);
        $elapsedMs = (microtime(true) - $started) * 1000;

        $response->assertStatus(200);
        $this->assertResponseWithinMs($elapsedMs, $maxMs, 'PUT api/tasks/{id} (status=COMPLETED)');
    }

    public function testReassignTaskUpdateResponseTime(): void
    {
        $maxMs = $this->maxResponseTimeMsForTaskUpdate();

        $assignee = User::factory()->create();
        $token = ProcessRequestToken::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'ACTIVE',
        ]);

        // Prevent notification errors by faking the notification system
        // The factory generates random element_ids that don't exist in the BPMN document
        Notification::fake();

        $started = microtime(true);
        $response = $this->apiCall('PUT', '/tasks/' . $token->id, [
            'user_id' => $assignee->id,
            'comments' => 'response time test',
        ]);
        $elapsedMs = (microtime(true) - $started) * 1000;

        $response->assertStatus(200);
        $this->assertResponseWithinMs($elapsedMs, $maxMs, 'PUT api/tasks/{id} (reassign)');
    }
}
