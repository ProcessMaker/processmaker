<?php

namespace Tests\Unit\ProcessMaker\Models;

use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use Tests\TestCase;

class ProcessTest extends TestCase
{
    public function testGetConsolidatedUsers()
    {
        $process = Process::factory()->create();

        $groupA = Group::factory()->create(['name' => 'Group A', 'status' => 'ACTIVE']);
        $groupB = Group::factory()->create(['name' => 'Group B', 'status' => 'ACTIVE']);

        $groupAUser = User::factory()->create(['status' => 'ACTIVE']);
        $groupBUser = User::factory()->create(['status' => 'ACTIVE']);

        $groupA->groupMembers()->create(['member_id' => $groupAUser->id, 'member_type' => User::class]);
        $groupB->groupMembers()->create(['member_id' => $groupBUser->id, 'member_type' => User::class]);

        // Add group B to group A
        $groupA->groupMembers()->create(['member_id' => $groupB->id, 'member_type' => Group::class]);

        $users = [];
        $process->getConsolidatedUsers($groupA->id, $users);

        $this->assertEquals([$groupAUser->id, $groupBUser->id], $users);
    }
}
