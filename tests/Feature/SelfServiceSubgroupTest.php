<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\TestCase;

class SelfServiceSubgroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_subgroup_member_sees_and_can_claim_parent_group_self_service_task(): void
    {
        [$parent, $subgroup, $nestedUser, $directUser, $outsider] = $this->createNestedGroups();

        $task = ProcessRequestToken::factory()->create([
            'is_self_service' => true,
            'status' => 'ACTIVE',
            'user_id' => null,
            'self_service_groups' => ['groups' => [$parent->id]],
        ]);

        $this->assertContains($task->id, $nestedUser->availableSelfServiceTaskIds());
        $this->assertContains($task->id, $directUser->availableSelfServiceTaskIds());
        $this->assertNotContains($task->id, $outsider->availableSelfServiceTaskIds());

        Auth::login($nestedUser);
        $this->assertTrue($nestedUser->canSelfServe($task));

        Auth::login($directUser);
        $this->assertTrue($directUser->canSelfServe($task));

        Auth::login($outsider);
        $this->assertFalse($outsider->canSelfServe($task));

        $this->assertContains($parent->id, $nestedUser->selfServiceGroupIds());
        $this->assertNotContains($subgroup->id, $outsider->selfServiceGroupIds());
    }

    public function test_two_level_nested_subgroup_member_sees_self_service_task(): void
    {
        $parent = Group::factory()->create();
        $child = Group::factory()->create();
        $grandchild = Group::factory()->create();
        $user = User::factory()->create();

        GroupMember::factory()->create([
            'group_id' => $parent->id,
            'member_id' => $child->id,
            'member_type' => Group::class,
        ]);
        GroupMember::factory()->create([
            'group_id' => $child->id,
            'member_id' => $grandchild->id,
            'member_type' => Group::class,
        ]);
        $user->groups()->attach($grandchild);

        $task = ProcessRequestToken::factory()->create([
            'is_self_service' => true,
            'status' => 'ACTIVE',
            'user_id' => null,
            'self_service_groups' => ['groups' => [(string) $parent->id]],
        ]);

        $this->assertContains($task->id, $user->availableSelfServiceTaskIds());
        Auth::login($user);
        $this->assertTrue($user->canSelfServe($task));
    }

    public function test_removing_subgroup_from_parent_drops_cached_self_service_visibility(): void
    {
        [$parent, $subgroup, $nestedUser] = $this->createNestedGroups();

        $task = ProcessRequestToken::factory()->create([
            'is_self_service' => true,
            'status' => 'ACTIVE',
            'user_id' => null,
            'self_service_groups' => ['groups' => [$parent->id]],
        ]);

        $this->assertContains($task->id, $nestedUser->availableSelfServiceTaskIds());

        GroupMember::where('group_id', $parent->id)
            ->where('member_id', $subgroup->id)
            ->where('member_type', Group::class)
            ->first()
            ->delete();

        $this->assertNotContains($task->id, $nestedUser->fresh()->availableSelfServiceTaskIds());
    }

    public function test_exclude_qualifies_columns_with_the_model_table(): void
    {
        $task = ProcessRequestToken::factory()->create([
            'element_type' => 'task',
        ]);

        $found = ProcessRequestToken::exclude(['data'])->find($task->id);

        $this->assertNotNull($found);
        $this->assertSame($task->id, $found->id);
        $this->assertStringContainsString(
            '`process_request_tokens`.`id`',
            ProcessRequestToken::exclude(['data'])->toSql()
        );
    }

    public function test_subgroup_member_sees_parent_self_service_task_on_tasks_index(): void
    {
        [$parent, , $nestedUser] = $this->createNestedGroups();

        $task = ProcessRequestToken::factory()->create([
            'is_self_service' => true,
            'status' => 'ACTIVE',
            'user_id' => null,
            'element_type' => 'task',
            'self_service_groups' => ['groups' => [(string) $parent->id], 'users' => []],
        ]);

        $response = $this->actingAs($nestedUser, 'api')->getJson(route('api.tasks.index', [
            'pmql' => '(status = "Self Service")',
            'per_page' => 15,
            'order_by' => 'ID',
            'order_direction' => 'DESC',
            'non_system' => true,
            'processesIManage' => false,
        ]));

        $response->assertOk();
        $this->assertContains($task->id, collect($response->json('data'))->pluck('id'));
    }

    /**
     * @return array{0: Group, 1: Group, 2: User, 3: User, 4: User}
     */
    private function createNestedGroups(): array
    {
        $parent = Group::factory()->create(['name' => 'Main']);
        $subgroup = Group::factory()->create(['name' => 'Sub']);
        $nestedUser = User::factory()->create();
        $directUser = User::factory()->create();
        $outsider = User::factory()->create();

        GroupMember::factory()->create([
            'group_id' => $parent->id,
            'member_id' => $subgroup->id,
            'member_type' => Group::class,
        ]);
        $nestedUser->groups()->attach($subgroup);
        $directUser->groups()->attach($parent);

        return [$parent, $subgroup, $nestedUser, $directUser, $outsider];
    }
}
