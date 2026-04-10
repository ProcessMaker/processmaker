<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\TestCase;

class SelfServiceOptimizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * BULLETPROOF TEST: Verifies that the Subquery approach is 100% equivalent
     * to the Array approach across all possible edge cases and legacy formats.
     */
    public function test_subquery_optimization_is_bulletproof()
    {
        // 1. Setup Environment
        $user = User::factory()->create();
        $groupA = Group::factory()->create(['name' => 'Group A']);
        $groupB = Group::factory()->create(['name' => 'Group B']);
        $user->groups()->attach([$groupA->id, $groupB->id]);

        $otherUser = User::factory()->create();
        $otherGroup = Group::factory()->create(['name' => 'Other Group']);

        // 2. CREATE SCENARIOS

        // Scenario 1: New Format - Int ID in groups array
        $t1 = $this->createSelfServiceTask(['groups' => [$groupA->id]]);

        // Scenario 2: New Format - String ID in groups array (Legacy/JSON inconsistency)
        $t2 = $this->createSelfServiceTask(['groups' => [(string) $groupB->id]]);

        // Scenario 3: Old Format - Direct ID in array (Very old processes)
        $t3 = $this->createSelfServiceTask([$groupA->id]);

        // Scenario 4: Direct User Assignment (Int)
        $t4 = $this->createSelfServiceTask(['users' => [$user->id]]);

        // Scenario 5: Direct User Assignment (String)
        $t5 = $this->createSelfServiceTask(['users' => [(string) $user->id]]);

        // --- NEGATIVE SCENARIOS (Should NEVER be returned) ---

        // Scenario 6: Task for another group
        $t6 = $this->createSelfServiceTask(['groups' => [$otherGroup->id]]);

        // Scenario 7: Task for another user
        $t7 = $this->createSelfServiceTask(['users' => [$otherUser->id]]);

        // Scenario 8: Task is not ACTIVE
        $t8 = $this->createSelfServiceTask(['users' => [$user->id]], 'COMPLETED');

        // Scenario 9: Task is already assigned to someone
        $t9 = $this->createSelfServiceTask(['users' => [$user->id]], 'ACTIVE', $otherUser->id);

        // 3. THE COMPARISON ENGINE

        // Method A: Array Pluck (Memory intensive, prone to crash)
        $oldWayIds = $user->availableSelfServiceTaskIds()->sort()->values()->toArray();

        // Method B: Subquery (Optimized, safe)
        $newWayQuery = $user->availableSelfServiceTasksQuery();
        $resultsNewWay = ProcessRequestToken::whereIn('id', $newWayQuery)
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        // 4. ASSERTIONS

        // A. Integrity check: Both lists must be identical
        $this->assertEquals($oldWayIds, $resultsNewWay, 'FATAL: Subquery results differ from Array results!');

        // B. Coverage check: Ensure all positive scenarios are present
        $expectedIds = [$t1->id, $t2->id, $t3->id, $t4->id, $t5->id];
        sort($expectedIds);
        $this->assertEquals($expectedIds, $resultsNewWay, 'Subquery missed one of the valid scenarios.');

        // C. Exclusion check: Ensure none of the negative scenarios leaked in
        $forbiddenIds = [$t6->id, $t7->id, $t8->id, $t9->id];
        foreach ($forbiddenIds as $id) {
            $this->assertNotContains($id, $resultsNewWay, "Security breach: Task $id should not be visible.");
        }

        // D. Performance Logic check: Subquery must be an instance of Eloquent Builder
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $newWayQuery);
    }

    /**
     * STRESS TEST: Demonstrates the performance and stability gap.
     * This test creates 10,000 tasks to show how the old way struggles vs the new way.
     */
    public function test_large_data_performance_and_stability()
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        $user->groups()->attach($group);

        // Crear dependencias reales para evitar errores de Foreign Key
        $process = \ProcessMaker\Models\Process::factory()->create();
        $request = \ProcessMaker\Models\ProcessRequest::factory()->create([
            'process_id' => $process->id,
        ]);

        echo "\n--- STRESS TEST (10,000 Self-Service Tasks) ---\n";

        // 1. Seed 10,000 tasks efficiently using bulk insert
        $count = 10000;
        $now = now()->toDateTimeString();
        $chunkSize = 1000;

        for ($i = 0; $i < $count / $chunkSize; $i++) {
            $tasks = [];
            for ($j = 0; $j < $chunkSize; $j++) {
                $tasks[] = [
                    'process_id' => $process->id,
                    'process_request_id' => $request->id,
                    'element_id' => 'node_1',
                    'element_type' => 'task',
                    'status' => 'ACTIVE',
                    'is_self_service' => 1,
                    'self_service_groups' => json_encode(['groups' => [$group->id]]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('process_request_tokens')->insert($tasks);
        }

        // 2. Measure OLD WAY (Array of IDs)
        $startMemOld = memory_get_usage();
        $startTimeOld = microtime(true);

        $ids = $user->availableSelfServiceTaskIds();
        $resultOld = ProcessRequestToken::whereIn('id', $ids)->count();

        $timeOld = microtime(true) - $startTimeOld;
        $memOld = (memory_get_usage() - $startMemOld) / 1024 / 1024;

        // 3. Measure NEW WAY (Subquery)
        $startMemNew = memory_get_usage();
        $startTimeNew = microtime(true);

        $query = $user->availableSelfServiceTasksQuery();
        $resultNew = ProcessRequestToken::whereIn('id', $query)->count();

        $timeNew = microtime(true) - $startTimeNew;
        $memNew = (memory_get_usage() - $startMemNew) / 1024 / 1024;

        // OUTPUT RESULTS
        echo 'OLD WAY (Array):    Time: ' . number_format($timeOld, 4) . 's | Mem: ' . number_format($memOld, 2) . "MB | Found: $resultOld\n";
        echo 'NEW WAY (Subquery): Time: ' . number_format($timeNew, 4) . 's | Mem: ' . number_format($memNew, 2) . "MB | Found: $resultNew\n";

        // ASSERTIONS
        $this->assertEquals($resultOld, $resultNew, 'Results must be identical!');

        // En base de datos reales (no en memoria), la subconsulta suele ser más rápida
        // Pero lo más importante es que no tiene límites de placeholders
        $this->assertTrue($resultNew > 0);

        echo "----------------------------------------------\n";
        if ($timeNew > 0) {
            echo 'Optimization: ' . number_format(($timeOld / $timeNew), 1) . "x faster\n";
        }
    }

    private function createSelfServiceTask($groups, $status = 'ACTIVE', $userId = null)
    {
        return ProcessRequestToken::factory()->create([
            'is_self_service' => true,
            'status' => $status,
            'user_id' => $userId,
            'self_service_groups' => $groups,
        ]);
    }
}
