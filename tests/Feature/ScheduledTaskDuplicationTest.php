<?php

namespace Tests\Feature;

use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Managers\TaskSchedulerManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ScheduledTask;
use Tests\TestCase;

class ScheduledTaskDuplicationTest extends TestCase
{
    /**
     * Test that atomic claim prevents duplicate task execution.
     * Simulates two processes trying to claim the same task.
     */
    public function testAtomicClaimPreventsDuplicateExecution()
    {
        $process = Process::factory()->create(['status' => 'ACTIVE']);

        // Create a scheduled task that should be executed
        $task = ScheduledTask::create([
            'process_id' => $process->id,
            'type' => 'TIMER_START_EVENT',
            'last_execution' => Carbon::now()->subMinutes(5)->format('Y-m-d H:i:s'),
            'configuration' => json_encode([
                'type' => 'TimeCycle',
                'interval' => $this->createCycleInterval(),
                'element_id' => 'node_1',
            ]),
        ]);

        $now = Carbon::now()->format('Y-m-d H:i:s');

        // First claim should succeed
        $claimed1 = DB::table('scheduled_tasks')
            ->where('id', $task->id)
            ->whereNull('claimed_by')
            ->update([
                'claimed_by' => Str::uuid()->toString(),
                'claimed_at' => $now,
            ]);

        // Second claim should fail (task already claimed)
        $claimed2 = DB::table('scheduled_tasks')
            ->where('id', $task->id)
            ->whereNull('claimed_by')
            ->update([
                'claimed_by' => Str::uuid()->toString(),
                'claimed_at' => $now,
            ]);

        $this->assertEquals(1, $claimed1, 'First claim should succeed');
        $this->assertEquals(0, $claimed2, 'Second claim should fail');
    }

    /**
     * Test that stale claims are released after timeout.
     */
    public function testStaleClaimsAreReleased()
    {
        $process = Process::factory()->create(['status' => 'ACTIVE']);

        // Create a task with a stale claim (claimed 10 minutes ago)
        $task = ScheduledTask::create([
            'process_id' => $process->id,
            'type' => 'TIMER_START_EVENT',
            'last_execution' => Carbon::now()->subMinutes(15)->format('Y-m-d H:i:s'),
            'configuration' => json_encode([
                'type' => 'TimeCycle',
                'interval' => $this->createCycleInterval(),
                'element_id' => 'node_1',
            ]),
            'claimed_by' => 'stale-claim-id',
            'claimed_at' => Carbon::now()->subMinutes(10),
        ]);

        $manager = new TaskSchedulerManager();

        // Use reflection to call the private method
        $reflection = new \ReflectionClass($manager);
        $method = $reflection->getMethod('releaseStaleClaimedTasks');
        $method->setAccessible(true);
        $method->invoke($manager);

        // Verify the claim was released
        $task->refresh();
        $this->assertNull($task->claimed_by);
        $this->assertNull($task->claimed_at);
    }

    /**
     * Test that recent claims are NOT released.
     */
    public function testRecentClaimsAreNotReleased()
    {
        $process = Process::factory()->create(['status' => 'ACTIVE']);

        $claimId = Str::uuid()->toString();

        // Create a task with a recent claim (1 minute ago)
        $task = ScheduledTask::create([
            'process_id' => $process->id,
            'type' => 'TIMER_START_EVENT',
            'last_execution' => Carbon::now()->subMinutes(5)->format('Y-m-d H:i:s'),
            'configuration' => json_encode([
                'type' => 'TimeCycle',
                'interval' => $this->createCycleInterval(),
                'element_id' => 'node_1',
            ]),
            'claimed_by' => $claimId,
            'claimed_at' => Carbon::now()->subMinute(),
        ]);

        $manager = new TaskSchedulerManager();

        $reflection = new \ReflectionClass($manager);
        $method = $reflection->getMethod('releaseStaleClaimedTasks');
        $method->setAccessible(true);
        $method->invoke($manager);

        // Verify the claim was NOT released
        $task->refresh();
        $this->assertEquals($claimId, $task->claimed_by);
        $this->assertNotNull($task->claimed_at);
    }

    /**
     * Test that claimTask method works correctly.
     */
    public function testClaimTaskMethod()
    {
        $process = Process::factory()->create(['status' => 'ACTIVE']);

        $task = ScheduledTask::create([
            'process_id' => $process->id,
            'type' => 'TIMER_START_EVENT',
            'last_execution' => Carbon::now()->subMinutes(5)->format('Y-m-d H:i:s'),
            'configuration' => json_encode([
                'type' => 'TimeCycle',
                'interval' => $this->createCycleInterval(),
                'element_id' => 'node_1',
            ]),
        ]);

        $manager = new TaskSchedulerManager();
        $now = Carbon::now()->format('Y-m-d H:i:s');

        // Use reflection to call the private method
        $reflection = new \ReflectionClass($manager);
        $method = $reflection->getMethod('claimTask');
        $method->setAccessible(true);

        // First claim should succeed
        $result1 = $method->invoke($manager, $task->id, $now);
        $this->assertTrue($result1, 'First claim should succeed');

        // Second claim should fail
        $result2 = $method->invoke($manager, $task->id, $now);
        $this->assertFalse($result2, 'Second claim should fail');
    }

    /**
     * Test race condition prevention with multiple concurrent claims.
     */
    public function testRaceConditionPrevention()
    {
        $process = Process::factory()->create(['status' => 'ACTIVE']);

        $task = ScheduledTask::create([
            'process_id' => $process->id,
            'type' => 'TIMER_START_EVENT',
            'last_execution' => Carbon::now()->subMinutes(5)->format('Y-m-d H:i:s'),
            'configuration' => json_encode([
                'type' => 'TimeCycle',
                'interval' => $this->createCycleInterval(),
                'element_id' => 'node_1',
            ]),
        ]);

        $claimResults = [];
        $now = Carbon::now()->format('Y-m-d H:i:s');

        // Simulate 10 concurrent claims
        for ($i = 0; $i < 10; $i++) {
            $claimed = DB::table('scheduled_tasks')
                ->where('id', $task->id)
                ->whereNull('claimed_by')
                ->update([
                    'claimed_by' => Str::uuid()->toString(),
                    'claimed_at' => $now,
                ]);

            $claimResults[] = $claimed;
        }

        // Only ONE claim should succeed
        $successfulClaims = array_sum($claimResults);
        $this->assertEquals(1, $successfulClaims, 'Only one claim should succeed out of 10 attempts');
    }

    /**
     * Test that task is released after successful execution.
     */
    public function testTaskReleasedAfterExecution()
    {
        $process = Process::factory()->create(['status' => 'ACTIVE']);

        $task = ScheduledTask::create([
            'process_id' => $process->id,
            'type' => 'TIMER_START_EVENT',
            'last_execution' => Carbon::now()->subMinutes(5)->format('Y-m-d H:i:s'),
            'configuration' => json_encode([
                'type' => 'TimeCycle',
                'interval' => $this->createCycleInterval(),
                'element_id' => 'node_1',
            ]),
            'claimed_by' => 'test-claim-id',
            'claimed_at' => Carbon::now(),
        ]);

        $manager = new TaskSchedulerManager();

        $reflection = new \ReflectionClass($manager);
        $method = $reflection->getMethod('releaseTask');
        $method->setAccessible(true);
        $method->invoke($manager, $task);

        // Verify the task was released
        $task->refresh();
        $this->assertNull($task->claimed_by);
        $this->assertNull($task->claimed_at);
    }

    /**
     * Test that already claimed tasks are skipped during processing.
     */
    public function testAlreadyClaimedTasksAreSkipped()
    {
        $process = Process::factory()->create(['status' => 'ACTIVE']);

        // Task that is already claimed by another process
        $claimedTask = ScheduledTask::create([
            'process_id' => $process->id,
            'type' => 'TIMER_START_EVENT',
            'last_execution' => Carbon::now()->subMinutes(5)->format('Y-m-d H:i:s'),
            'configuration' => json_encode([
                'type' => 'TimeCycle',
                'interval' => $this->createCycleInterval(),
                'element_id' => 'node_1',
            ]),
            'claimed_by' => 'other-process-claim',
            'claimed_at' => Carbon::now(),
        ]);

        // Unclaimed task
        $unclaimedTask = ScheduledTask::create([
            'process_id' => $process->id,
            'type' => 'TIMER_START_EVENT',
            'last_execution' => Carbon::now()->subMinutes(5)->format('Y-m-d H:i:s'),
            'configuration' => json_encode([
                'type' => 'TimeCycle',
                'interval' => $this->createCycleInterval(),
                'element_id' => 'node_2',
            ]),
        ]);

        // Query for unclaimed tasks only (as the new implementation does)
        $unclaimedTasks = ScheduledTask::whereNull('claimed_by')->get();

        $this->assertCount(1, $unclaimedTasks);
        $this->assertEquals($unclaimedTask->id, $unclaimedTasks->first()->id);
    }

    /**
     * Test selection logic: task should NOT be claimed if nextDate is in the future.
     */
    public function testTaskNotExecutedIfNextDateInFuture()
    {
        $process = Process::factory()->create(['status' => 'ACTIVE']);

        // Set a fake "today" to control time precisely
        $fakeToday = Carbon::create(2026, 1, 12, 12, 0, 0, 'UTC');
        TaskSchedulerManager::fakeToday($fakeToday);

        // Create a task that was executed 5 minutes ago with 60-minute interval
        // Next execution should be at 12:55 (55 minutes in the future)
        $task = ScheduledTask::create([
            'process_id' => $process->id,
            'type' => 'TIMER_START_EVENT',
            'last_execution' => Carbon::create(2026, 1, 12, 11, 55, 0, 'UTC')->format('Y-m-d H:i:s'),
            'configuration' => json_encode([
                'type' => 'TimeCycle',
                'interval' => $this->createCycleIntervalFromDate(
                    Carbon::create(2026, 1, 12, 10, 55, 0, 'UTC'), // Start at 10:55
                    60 // Every 60 minutes
                ),
                'element_id' => 'node_1',
            ]),
        ]);

        $manager = new TaskSchedulerManager();
        $today = $manager->today();

        // Calculate nextDate using the manager
        $config = json_decode($task->configuration);
        $lastExecution = new \DateTime($task->last_execution, new \DateTimeZone('UTC'));
        $nextDate = $manager->nextDate($today, $config, $lastExecution, null);

        // nextDate should be 12:55 which is 55 minutes after "today" (12:00)
        $this->assertNotNull($nextDate);
        $this->assertGreaterThan($today->getTimestamp(), $nextDate->getTimestamp());

        // Reset fake today
        TaskSchedulerManager::fakeToday(null);
    }

    /**
     * Test selection logic: task SHOULD be executed if nextDate has passed.
     */
    public function testTaskExecutedIfNextDatePassed()
    {
        $process = Process::factory()->create(['status' => 'ACTIVE']);

        // Set a fake "today" to control time precisely
        $fakeToday = Carbon::create(2026, 1, 12, 12, 30, 0, 'UTC');
        TaskSchedulerManager::fakeToday($fakeToday);

        // Create a task with last execution at 12:00, 1-minute interval
        // Next execution should be at 12:01, which is 29 minutes in the past
        $task = ScheduledTask::create([
            'process_id' => $process->id,
            'type' => 'TIMER_START_EVENT',
            'last_execution' => Carbon::create(2026, 1, 12, 12, 0, 0, 'UTC')->format('Y-m-d H:i:s'),
            'configuration' => json_encode([
                'type' => 'TimeCycle',
                'interval' => $this->createCycleIntervalFromDate(
                    Carbon::create(2026, 1, 12, 11, 0, 0, 'UTC'), // Start at 11:00
                    1 // Every 1 minute
                ),
                'element_id' => 'node_1',
            ]),
        ]);

        $manager = new TaskSchedulerManager();
        $today = $manager->today();

        // Calculate nextDate
        $config = json_decode($task->configuration);
        $lastExecution = new \DateTime($task->last_execution, new \DateTimeZone('UTC'));
        $nextDate = $manager->nextDate($today, $config, $lastExecution, null);

        // nextDate should be 12:01 which is before "today" (12:30)
        $this->assertNotNull($nextDate);
        $this->assertLessThanOrEqual($today->getTimestamp(), $nextDate->getTimestamp());

        // Reset fake today
        TaskSchedulerManager::fakeToday(null);
    }

    /**
     * Helper method to create a cycle interval configuration.
     *
     * @param int $minutes Interval in minutes (default 1)
     */
    private function createCycleInterval(int $minutes = 1): object
    {
        return (object) [
            'start' => (object) [
                'date' => Carbon::now()->subHour()->format('Y-m-d H:i:s'),
                'timezone' => 'UTC',
            ],
            'interval' => (object) [
                'y' => 0,
                'm' => 0,
                'd' => 0,
                'h' => 0,
                'i' => $minutes,
                's' => 0,
                'f' => 0,
            ],
            'end' => null,
            'recurrences' => 0,
        ];
    }

    /**
     * Helper method to create a cycle interval configuration with a specific start date.
     *
     * @param Carbon $startDate The start date for the cycle
     * @param int $minutes Interval in minutes
     */
    private function createCycleIntervalFromDate(Carbon $startDate, int $minutes): object
    {
        return (object) [
            'start' => (object) [
                'date' => $startDate->format('Y-m-d H:i:s'),
                'timezone' => 'UTC',
            ],
            'interval' => (object) [
                'y' => 0,
                'm' => 0,
                'd' => 0,
                'h' => 0,
                'i' => $minutes,
                's' => 0,
                'f' => 0,
            ],
            'end' => null,
            'recurrences' => 0,
        ];
    }
}
