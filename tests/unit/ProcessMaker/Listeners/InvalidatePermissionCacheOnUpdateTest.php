<?php

namespace Tests\Unit\ProcessMaker\Listeners;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Events\PermissionUpdated;
use ProcessMaker\Listeners\InvalidatePermissionCacheOnUpdate;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\User;
use Tests\TestCase;

class InvalidatePermissionCacheOnUpdateTest extends TestCase
{
    use RefreshDatabase;

    private InvalidatePermissionCacheOnUpdate $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new InvalidatePermissionCacheOnUpdate(
            app(\ProcessMaker\Services\PermissionServiceManager::class)
        );

        Cache::flush();
    }

    public function test_invalidates_only_affected_group_users_and_preserves_unrelated_cache_entries()
    {
        $permission = Permission::factory()->create(['name' => 'test-permission']);
        $group = Group::factory()->create(['name' => 'Target Group']);
        $childGroup = Group::factory()->create(['name' => 'Child Group']);
        $unrelatedGroup = Group::factory()->create(['name' => 'Unrelated Group']);
        $directUser = User::factory()->create(['username' => 'direct-user']);
        $childUser = User::factory()->create(['username' => 'child-user']);
        $unrelatedUser = User::factory()->create(['username' => 'unrelated-user']);

        $group->permissions()->attach($permission->id);

        $group->groupMembers()->create([
            'group_id' => $group->id,
            'member_id' => $directUser->id,
            'member_type' => User::class,
        ]);

        $group->groupMembers()->create([
            'group_id' => $group->id,
            'member_id' => $childGroup->id,
            'member_type' => Group::class,
        ]);

        $childGroup->groupMembers()->create([
            'group_id' => $childGroup->id,
            'member_id' => $childUser->id,
            'member_type' => User::class,
        ]);

        $unrelatedGroup->groupMembers()->create([
            'group_id' => $unrelatedGroup->id,
            'member_id' => $unrelatedUser->id,
            'member_type' => User::class,
        ]);

        $permissionService = app(\ProcessMaker\Services\PermissionServiceManager::class);
        $permissionService->warmUpUserCache($directUser->id);
        $permissionService->warmUpUserCache($childUser->id);
        $permissionService->warmUpUserCache($unrelatedUser->id);

        Cache::put("user_{$directUser->id}_permissions", ['legacy'], 3600);
        Cache::put("user_{$childUser->id}_permissions", ['legacy'], 3600);
        Cache::put("user_{$unrelatedUser->id}_permissions", ['legacy'], 3600);
        Cache::put("group_permissions:{$group->id}", ['group-permission'], 3600);
        Cache::put('unrelated-cache-key', 'keep-me', 3600);

        $event = new PermissionUpdated(
            ['test-permission'],
            [],
            false,
            null,
            (string) $group->id
        );

        $this->listener->handle($event);

        $this->assertNull(Cache::get("user_permissions:{$directUser->id}"));
        $this->assertNull(Cache::get("user_permissions:{$childUser->id}"));
        $this->assertNull(Cache::get("user_{$directUser->id}_permissions"));
        $this->assertNull(Cache::get("user_{$childUser->id}_permissions"));
        $this->assertNull(Cache::get("group_permissions:{$group->id}"));

        $this->assertNotNull(Cache::get("user_permissions:{$unrelatedUser->id}"));
        $this->assertNotNull(Cache::get("user_{$unrelatedUser->id}_permissions"));
        $this->assertSame('keep-me', Cache::get('unrelated-cache-key'));
    }

    public function test_invalidates_legacy_and_new_cache_for_user_updates()
    {
        $user = User::factory()->create(['username' => 'target-user']);

        Cache::put("user_permissions:{$user->id}", ['new-cache'], 3600);
        Cache::put("user_{$user->id}_permissions", ['legacy-cache'], 3600);
        Cache::put('unrelated-cache-key', 'keep-me', 3600);

        $event = new PermissionUpdated(
            ['edit-users'],
            [],
            false,
            (string) $user->id,
            null
        );

        $this->listener->handle($event);

        $this->assertNull(Cache::get("user_permissions:{$user->id}"));
        $this->assertNull(Cache::get("user_{$user->id}_permissions"));
        $this->assertSame('keep-me', Cache::get('unrelated-cache-key'));
    }
}
