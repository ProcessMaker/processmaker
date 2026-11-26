<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    public function test_cache_filled_during_group_delete_window_is_not_reused(): void
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

        // Simulate a concurrent request that refills the cache while the group delete
        // is in progress (before the cascade has removed the membership rows).
        $armed = true;
        Group::deleting(function () use ($user, &$armed) {
            if ($armed) {
                $user->selfServiceGroupIds();
            }
        });

        try {
            $group->delete();
        } finally {
            $armed = false;
        }

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

    public function test_update_groups_api_drops_cached_self_service_visibility(): void
    {
        $admin = User::factory()->create(['is_administrator' => true]);
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
        $this->actingAs($user, 'web');
        $this->assertTrue($user->canSelfServe($task));

        $response = $this->actingAs($admin, 'api')
            ->putJson(route('api.users.groups.update', $user), ['groups' => []]);

        $response->assertNoContent();

        $user = $user->fresh();
        $this->actingAs($user, 'web');

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

    public function test_in_flight_cache_fill_cannot_repopulate_key_after_detach(): void
    {
        $this->assertInFlightFillIsNotReusedAfterRevocation(
            fn (User $user, Group $group) => $user->groups()->detach($group->id)
        );
    }

    public function test_in_flight_cache_fill_cannot_repopulate_key_after_sync(): void
    {
        $this->assertInFlightFillIsNotReusedAfterRevocation(
            fn (User $user, Group $group) => $user->groups()->sync([])
        );
    }

    public function test_in_flight_cache_fill_cannot_repopulate_key_after_group_member_delete(): void
    {
        $this->assertInFlightFillIsNotReusedAfterRevocation(
            fn (User $user, Group $group) => GroupMember::where('group_id', $group->id)
                ->where('member_id', $user->id)
                ->where('member_type', User::class)
                ->first()
                ->delete()
        );
    }

    /**
     * Simulates a request whose Cache::remember already read group_members (pre-revocation)
     * when the membership is revoked and committed. The late cache write must not be
     * readable by requests that start after the revocation.
     */
    private function assertInFlightFillIsNotReusedAfterRevocation(callable $revoke): void
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

        $armed = true;
        DB::listen(function (QueryExecuted $query) use (&$armed, $revoke, $user, $group) {
            // Fires after the membership query has already returned its (pre-revocation) rows.
            if ($armed && str_contains($query->sql, 'group_members')) {
                $armed = false;
                $revoke($user, $group);
            }
        });

        try {
            $inFlight = $user->selfServiceGroupIds()->all();
        } finally {
            $armed = false;
        }

        // The in-flight request itself legitimately observed the pre-revocation state.
        $this->assertSame([$group->id], $inFlight);

        // Requests that start after the revocation must not see it.
        $user = $user->fresh();
        Auth::login($user);

        $this->assertSame([], $user->selfServiceGroupIds()->all());
        $this->assertFalse($user->canSelfServe($task));
    }
}
