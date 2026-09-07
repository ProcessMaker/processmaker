<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1_1;

use Illuminate\Support\Facades\Config;
use Mockery;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessTaskAssignment;
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

    public function testComparesLegacyAndOptimizedTaskUpdateWithLargePayload(): void
    {
        $jsonFixturePath = __DIR__ . '/../../../Fixtures/json_optimizer_test_example.json';

        if (!file_exists($jsonFixturePath)) {
            $this->markTestSkipped('JSON fixture file not found: ' . $jsonFixturePath);
        }

        $fixture = json_decode((string) file_get_contents($jsonFixturePath), true);
        $this->assertIsArray($fixture);
        $this->assertIsArray($fixture['data'] ?? null);

        $payload = [
            'status' => 'COMPLETED',
            'data' => $fixture['data'],
        ];
        $iterations = 3;

        $measure = function (bool $taskUpdateEnabled, bool $rawPersistenceEnabled, string $routeName) use ($payload, $iterations): float {
            Config::set('app.task_update_v1_1_enabled', $taskUpdateEnabled);
            Config::set('app.token_persistence_raw_enabled', $rawPersistenceEnabled);

            $startedAt = microtime(true);

            for ($iteration = 0; $iteration < $iterations; $iteration++) {
                $process = Process::factory()->create([
                    'bpmn' => Process::getProcessTemplate('SingleTask.bpmn'),
                ]);
                ProcessTaskAssignment::factory()->create([
                    'process_id' => $process->id,
                    'process_task_id' => 'UserTaskUID',
                    'assignment_id' => $this->user->id,
                    'assignment_type' => User::class,
                ]);

                $startResponse = $this->apiCall(
                    'POST',
                    route('api.process_events.trigger', [
                        'process' => $process->id,
                        'event' => 'StartEventUID',
                    ]),
                    []
                );
                $startResponse->assertStatus(201);

                $task = ProcessRequestToken::query()
                    ->where('process_id', $process->id)
                    ->where('user_id', $this->user->id)
                    ->where('status', 'ACTIVE')
                    ->firstOrFail();

                $response = $this->apiCall(
                    'PUT',
                    route($routeName, $task->id),
                    $payload
                );

                $response->assertStatus(200);
            }

            return microtime(true) - $startedAt;
        };

        $legacyTime = $measure(false, false, 'api.tasks.update');
        $optimizedTime = $measure(true, true, 'api.1.1.tasks.update');
        $jsonSize = strlen((string) json_encode($payload));

        $legacyAverage = ($legacyTime / $iterations) * 1000;
        $optimizedAverage = ($optimizedTime / $iterations) * 1000;
        $improvement = $legacyAverage > 0
            ? (($legacyAverage - $optimizedAverage) / $legacyAverage) * 100
            : 0;

        fwrite(STDOUT, sprintf(
            "\nLarge task payload: %s bytes\nAPI 1.0 average: %.2f ms\nAPI 1.1 average: %.2f ms\nPerformance improvement: %.2f%%\n",
            number_format($jsonSize),
            $legacyAverage,
            $optimizedAverage,
            $improvement
        ));
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
