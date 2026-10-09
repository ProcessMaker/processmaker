<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1_1;

use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Compare GET api/1.1/tasks/{id} legacy show vs API_FAST_TASK showFast.
 *
 * TASK_SHOW_FAST_MAX_RATIO: fast_ms must be <= legacy_ms * ratio (default 0.53 ≈ 47% faster; use 0.4 for ~60% on heavier DB).
 *
 * @group process_tests
 * @group task_show_fast_timing
 */
class TaskShowFastResponseTimeTest extends TestCase
{
    use RequestHelper;

    /** Same heavy payload keys as screen-builder task load, without BPMN/screen-only includes. */
    private const LEGACY_TASK_INCLUDES =
        'data,user,draft,requestor,processRequest,requestData,loopContext,userRequestPermission,elementDestination';

    private function maxFastToLegacyRatio(): float
    {
        $env = getenv('TASK_SHOW_FAST_MAX_RATIO');
        if ($env !== false && $env !== '' && is_numeric($env)) {
            return (float) $env;
        }

        return 0.53;
    }

    /**
     * @return array{0: ProcessRequestToken, 1: string}
     */
    private function createTaskWithLargeRequestData(int $entries = 4000, int $valueLength = 80): array
    {
        $payload = [];
        for ($i = 0; $i < $entries; $i++) {
            $payload['bulk_field_' . $i] = str_repeat('x', $valueLength);
        }
        $payload['marker'] = 'task_show_fast_benchmark';

        $request = ProcessRequest::factory()->create([
            'data' => $payload,
            'status' => 'ACTIVE',
        ]);

        $task = ProcessRequestToken::factory()->create([
            'user_id' => $this->user->id,
            'process_request_id' => $request->id,
            'process_id' => $request->process_id,
            'status' => 'ACTIVE',
            'element_type' => 'task',
        ]);

        return [$task, self::LEGACY_TASK_INCLUDES];
    }

    private function measureTaskShowMs(ProcessRequestToken $task, string $includes): float
    {
        $url = route('api.1.1.tasks.show', $task->id)
            . '?include=' . urlencode($includes);

        $started = microtime(true);
        $response = $this->apiCall('GET', $url);
        $elapsedMs = (microtime(true) - $started) * 1000;

        $response->assertStatus(200);

        return $elapsedMs;
    }

    private function medianMs(array $samples): float
    {
        sort($samples);
        $count = count($samples);
        $middle = (int) floor($count / 2);

        if ($count % 2 === 1) {
            return $samples[$middle];
        }

        return ($samples[$middle - 1] + $samples[$middle]) / 2;
    }

    public function testShowFastRequestDataMatchesLegacyShow(): void
    {
        [$task, $includes] = $this->createTaskWithLargeRequestData(500, 40);

        config(['app.api_fast_task' => false]);
        $legacy = $this->apiCall(
            'GET',
            route('api.1.1.tasks.show', $task->id) . '?include=' . urlencode($includes)
        );
        $legacy->assertStatus(200);

        config(['app.api_fast_task' => true]);
        $fast = $this->apiCall(
            'GET',
            route('api.1.1.tasks.show', $task->id) . '?include=' . urlencode($includes)
        );
        $fast->assertStatus(200);

        $this->assertSame(
            $legacy->json('request_data.marker'),
            $fast->json('request_data.marker')
        );
        $this->assertSame(
            count($legacy->json('request_data')),
            count($fast->json('request_data'))
        );
        $this->assertArrayNotHasKey('data', $fast->json());
    }

    public function testShowFastIsFasterThanLegacyShowWithLargeRequestData(): void
    {
        [$task, $includes] = $this->createTaskWithLargeRequestData(12000, 100);
        $ratio = $this->maxFastToLegacyRatio();
        $iterations = 3;

        config(['app.api_fast_task' => false]);
        $legacySamples = [];
        for ($i = 0; $i < $iterations; $i++) {
            $legacySamples[] = $this->measureTaskShowMs($task, $includes);
        }
        $legacyMs = $this->medianMs($legacySamples);

        config(['app.api_fast_task' => true]);
        $fastSamples = [];
        for ($i = 0; $i < $iterations; $i++) {
            $fastSamples[] = $this->measureTaskShowMs($task, $includes);
        }
        $fastMs = $this->medianMs($fastSamples);

        $this->assertLessThan(
            $legacyMs,
            $fastMs,
            sprintf(
                'showFast median %.2fms should be less than legacy median %.2fms',
                $fastMs,
                $legacyMs
            )
        );

        $this->assertLessThanOrEqual(
            $legacyMs * $ratio,
            $fastMs,
            sprintf(
                'showFast median %.2fms exceeded legacy median %.2fms * ratio %.2f (set TASK_SHOW_FAST_MAX_RATIO to relax)',
                $fastMs,
                $legacyMs,
                $ratio
            )
        );
    }
}
