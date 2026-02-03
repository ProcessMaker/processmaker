<?php

namespace Tests\Unit\ProcessMaker\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\TestCase;

class SelfServiceComparisonTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifies that the results obtained using the ID list (Array)
     * and the subquery (Query Builder) are exactly the same.
     *
     * @return void
     */
    public function test_self_service_results_are_identical_between_array_and_subquery()
    {
        // 1. Scenario configuration
        $user = User::factory()->create();
        $group = Group::factory()->create();
        $user->groups()->attach($group);

        // Task 1: Available by GROUP (Should appear)
        $task1 = ProcessRequestToken::factory()->create([
            'is_self_service' => true,
            'status' => 'ACTIVE',
            'user_id' => null,
            'self_service_groups' => ['groups' => [$group->id]],
        ]);

        // Task 2: Available by direct USER (Should appear)
        $task2 = ProcessRequestToken::factory()->create([
            'is_self_service' => true,
            'status' => 'ACTIVE',
            'user_id' => null,
            'self_service_groups' => ['users' => [$user->id]],
        ]);

        // Task 3: NOT available (Another group)
        ProcessRequestToken::factory()->create([
            'is_self_service' => true,
            'status' => 'ACTIVE',
            'user_id' => null,
            'self_service_groups' => ['groups' => [9999]],
        ]);

        // Task 4: NOT available (Already completed)
        ProcessRequestToken::factory()->create([
            'is_self_service' => true,
            'status' => 'COMPLETED',
            'user_id' => null,
            'self_service_groups' => ['groups' => [$group->id]],
        ]);

        // 2. Execution of both methods

        // Method A: Using the IDs array (Preserved behavior)
        $idsArray = $user->availableSelfServiceTaskIds();
        $resultsFromArray = ProcessRequestToken::whereIn('id', $idsArray)
            ->pluck('id')
            ->sort()
            ->values()
            ->toArray();

        // Method B: Using the subquery (New optimization)
        $subqueryBuilder = $user->availableSelfServiceTasksQuery();
        $resultsFromSubquery = ProcessRequestToken::whereIn('id', $subqueryBuilder)
            ->pluck('id')
            ->sort()
            ->values()
            ->toArray();

        // 3. Verification

        // Check that tasks were found
        $this->assertCount(2, $resultsFromArray, 'Exactly 2 tasks should have been found with the old method.');

        // Check that both methods return exactly the same results
        $this->assertEquals(
            $resultsFromArray,
            $resultsFromSubquery,
            'Task IDs found by both methods MUST be identical.'
        );

        // Verify specific IDs are correct
        $this->assertContains($task1->id, $resultsFromSubquery);
        $this->assertContains($task2->id, $resultsFromSubquery);
    }
}
