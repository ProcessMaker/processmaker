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

class SelfServiceGroupCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_detach_via_sync_drops_cached_self_service_visibility(): void
    {
        $group = Group::factory()->create();
        $user = User::factory()->create();
        $user->groups()->attach($group);

        $task = ProcessRequestToken::factory()->create([
            'is_self_service' => true,
            'status' => 'ACTIVE',
            'user_id' => null,
            'self_service_groups' => ['groups' => [$group->id]],
        ]);

        $this->assertContains($group->id, $user->selfServiceGroupIds()->all());
        $this->assertContains($task->id, $user->availableSelfServiceTaskIds());

        Auth::login($user);
        $this->assertTrue($user->canSelfServe($task));

        $user->groups()->sync([]);

        $user = $user->fresh();
        Auth::login($user);

        $this->assertSame([], $user->selfServiceGroupIds()->all());
        $this->assertNotContains($task->id, $user->availableSelfServiceTaskIds());
        $this->assertFalse($user->canSelfServe($task));
    }

    public function test_remove_from_groups_drops_cached_self_service_visibility(): void
    {
        $group = Group::factory()->create();
        $user = User::factory()->create();
        $user->groups()->attach($group);

        $task = ProcessRequestToken::factory()->create([
            'is_self_service' => true,
            'status' => 'ACTIVE',
            'user_id' => null,
            'self_service_groups' => ['groups' => [$group->id]],
        ]);

        $user->selfServiceGroupIds();
        $user->removeFromGroups();

        $user = $user->fresh();
        Auth::login($user);

        $this->assertSame([], $user->selfServiceGroupIds()->all());
        $this->assertFalse($user->canSelfServe($task));
    }

    public function test_group_delete_drops_cached_self_service_visibility(): void
    {
        $group = Group::factory()->create();
        $user = User::factory()->create();
        $user->groups()->attach($group);

        $task = ProcessRequestToken::factory()->create([
            'is_self_service' => true,
            'status' => 'ACTIVE',
            'user_id' => null,
            'self_service_groups' => ['groups' => [$group->id]],
        ]);

        $user->selfServiceGroupIds();
        $group->delete();

        $user = $user->fresh();
        Auth::login($user);

        $this->assertSame([], $user->selfServiceGroupIds()->all());
        $this->assertFalse($user->canSelfServe($task));
    }

    public function test_group_member_delete_via_eloquent_drops_cached_self_service_visibility(): void
    {
        $group = Group::factory()->create();
        $user = User::factory()->create();
        $membership = GroupMember::factory()->create([
            'group_id' => $group->id,
            'member_id' => $user->id,
            'member_type' => User::class,
        ]);

        $task = ProcessRequestToken::factory()->create([
            'is_self_service' => true,
            'status' => 'ACTIVE',
            'user_id' => null,
            'self_service_groups' => ['groups' => [$group->id]],
        ]);

        $user->selfServiceGroupIds();
        $membership->delete();

        $user = $user->fresh();
        Auth::login($user);

        $this->assertSame([], $user->selfServiceGroupIds()->all());
        $this->assertFalse($user->canSelfServe($task));
    }
}
