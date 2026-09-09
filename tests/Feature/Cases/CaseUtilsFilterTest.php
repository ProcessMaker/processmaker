<?php

namespace Tests\Feature\Cases;

use Illuminate\Support\Collection;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Repositories\CaseUtils;
use Tests\TestCase;

class CaseUtilsFilterTest extends TestCase
{
    public function test_filter_tasks_by_user_resolves_missing_user_id_from_process_request_tokens()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $token1 = ProcessRequestToken::factory()->create([
            'user_id' => $user1->id,
            'element_type' => 'task',
            'status' => 'ACTIVE',
        ]);
        $token2 = ProcessRequestToken::factory()->create([
            'user_id' => $user2->id,
            'element_type' => 'task',
            'status' => 'ACTIVE',
        ]);
        $token3 = ProcessRequestToken::factory()->create([
            'user_id' => $user1->id,
            'element_type' => 'task',
            'status' => 'CLOSED',
        ]);

        // Legacy snapshots omit user_id; assignees must be resolved from tokens.
        $tasks = new Collection([
            ['id' => (string) $token1->id, 'name' => 'Task A', 'status' => 'ACTIVE'],
            ['id' => (string) $token2->id, 'name' => 'Task B', 'status' => 'ACTIVE'],
            ['id' => (string) $token3->id, 'name' => 'Task C', 'status' => 'CLOSED'],
        ]);

        $result = CaseUtils::filterTasksByUser($tasks, $user1->id);

        $this->assertEquals(2, $result->count());
        $this->assertEquals(
            [(string) $token1->id, (string) $token3->id],
            $result->pluck('id')->all()
        );
    }
}
