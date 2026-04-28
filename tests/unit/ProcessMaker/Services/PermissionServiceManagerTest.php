<?php

namespace Tests\Unit\ProcessMaker\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\User;
use ProcessMaker\Services\PermissionServiceManager;
use Tests\TestCase;

class PermissionServiceManagerTest extends TestCase
{
    use RefreshDatabase;

    private PermissionServiceManager $serviceManager;

    private User $user;

    private Group $group;

    private Permission $permission;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serviceManager = app(PermissionServiceManager::class);

        // Create test data
        $this->permission = Permission::factory()->create(['name' => 'test-permission']);
        $this->group = Group::factory()->create(['name' => 'Test Group']);
        $this->user = User::factory()->create(['username' => 'testuser']);

        // Clear cache
        Cache::flush();
    }

    /**
     * Test that getUserPermissions returns cached permissions when available
     */
    public function test_get_user_permissions_returns_cached_permissions()
    {
        // Add user to group with permission
        $this->user->groupMembersFromMemberable()->create([
            'group_id' => $this->group->id,
            'member_id' => $this->user->id,
            'member_type' => User::class,
        ]);
        $this->group->permissions()->attach($this->permission->id);

        // Warm up cache
        $this->serviceManager->warmUpUserCache($this->user->id);

        // Verify cache exists
        $this->assertNotNull(Cache::get("user_permissions:{$this->user->id}"));

        // Get permissions (should come from cache)
        $permissions = $this->serviceManager->getUserPermissions($this->user->id);

        // Assert that permissions are returned
        $this->assertContains('test-permission', $permissions);
    }

    /**
     * Test that getUserPermissions fetches from repository when cache is empty
     */
    public function test_get_user_permissions_fetches_from_repository_when_cache_empty()
    {
        // Add user to group with permission
        $this->user->groupMembersFromMemberable()->create([
            'group_id' => $this->group->id,
            'member_id' => $this->user->id,
            'member_type' => User::class,
        ]);
        $this->group->permissions()->attach($this->permission->id);

        // Verify cache is empty
        $this->assertNull(Cache::get("user_permissions:{$this->user->id}"));

        // Get permissions (should fetch from repository)
        $permissions = $this->serviceManager->getUserPermissions($this->user->id);

        // Assert that permissions are returned
        $this->assertContains('test-permission', $permissions);

        // Verify cache was populated
        $this->assertNotNull(Cache::get("user_permissions:{$this->user->id}"));
    }

    /**
     * Test that userHasPermission works correctly
     */
    public function test_user_has_permission_works_correctly()
    {
        // Add user to group with permission
        $this->user->groupMembersFromMemberable()->create([
            'group_id' => $this->group->id,
            'member_id' => $this->user->id,
            'member_type' => User::class,
        ]);
        $this->group->permissions()->attach($this->permission->id);

        // Test that user has the permission
        $this->assertTrue($this->serviceManager->userHasPermission($this->user->id, 'test-permission'));
        $this->assertFalse($this->serviceManager->userHasPermission($this->user->id, 'non-existent-permission'));
    }

    /**
     * Test that warmUpUserCache populates cache correctly
     */
    public function test_warm_up_user_cache_populates_cache_correctly()
    {
        // Add user to group with permission
        $this->user->groupMembersFromMemberable()->create([
            'group_id' => $this->group->id,
            'member_id' => $this->user->id,
            'member_type' => User::class,
        ]);
        $this->group->permissions()->attach($this->permission->id);

        // Verify cache is empty initially
        $this->assertNull(Cache::get("user_permissions:{$this->user->id}"));

        // Warm up cache
        $this->serviceManager->warmUpUserCache($this->user->id);

        // Verify cache was populated
        $this->assertNotNull(Cache::get("user_permissions:{$this->user->id}"));

        // Verify cache contains correct data
        $cachedPermissions = Cache::get("user_permissions:{$this->user->id}");
        $this->assertContains('test-permission', $cachedPermissions);
    }

    /**
     * Test that invalidateUserCache clears cache correctly
     */
    public function test_invalidate_user_cache_clears_cache_correctly()
    {
        // Add user to group with permission
        $this->user->groupMembersFromMemberable()->create([
            'group_id' => $this->group->id,
            'member_id' => $this->user->id,
            'member_type' => User::class,
        ]);
        $this->group->permissions()->attach($this->permission->id);

        // Warm up cache
        $this->serviceManager->warmUpUserCache($this->user->id);

        // Verify cache exists
        $this->assertNotNull(Cache::get("user_permissions:{$this->user->id}"));

        // Invalidate cache
        $this->serviceManager->invalidateUserCache($this->user->id);

        // Verify cache was cleared
        $this->assertNull(Cache::get("user_permissions:{$this->user->id}"));
    }

    /**
     * Test that hierarchical inheritance works through the service manager
     */
    public function test_hierarchical_inheritance_works_through_service_manager()
    {
        // Create nested groups
        $parentGroup = Group::factory()->create(['name' => 'Parent Group']);
        $childGroup = Group::factory()->create(['name' => 'Child Group']);

        // Set up hierarchy: User → ChildGroup → ParentGroup
        $this->user->groupMembersFromMemberable()->create([
            'group_id' => $childGroup->id,
            'member_id' => $this->user->id,
            'member_type' => User::class,
        ]);

        $childGroup->groupMembersFromMemberable()->create([
            'group_id' => $parentGroup->id,
            'member_id' => $childGroup->id,
            'member_type' => Group::class,
        ]);

        // Add permission to parent group
        $parentGroup->permissions()->attach($this->permission->id);

        // Test that user has the inherited permission
        $this->assertTrue($this->serviceManager->userHasPermission($this->user->id, 'test-permission'));

        // Get all user permissions
        $permissions = $this->serviceManager->getUserPermissions($this->user->id);
        $this->assertContains('test-permission', $permissions);
    }

    /**
     * Test that cache is properly managed for multiple users
     */
    public function test_cache_is_properly_managed_for_multiple_users()
    {
        // Create second user
        $user2 = User::factory()->create(['username' => 'testuser2']);

        // Add both users to the same group
        $this->user->groupMembersFromMemberable()->create([
            'group_id' => $this->group->id,
            'member_id' => $this->user->id,
            'member_type' => User::class,
        ]);

        $user2->groupMembersFromMemberable()->create([
            'group_id' => $this->group->id,
            'member_id' => $user2->id,
            'member_type' => User::class,
        ]);

        $this->group->permissions()->attach($this->permission->id);

        // Warm up cache for both users
        $this->serviceManager->warmUpUserCache($this->user->id);
        $this->serviceManager->warmUpUserCache($user2->id);

        // Verify both caches exist
        $this->assertNotNull(Cache::get("user_permissions:{$this->user->id}"));
        $this->assertNotNull(Cache::get("user_permissions:{$user2->id}"));

        // Invalidate cache for first user only
        $this->serviceManager->invalidateUserCache($this->user->id);

        // Verify first user cache was cleared, second user cache remains
        $this->assertNull(Cache::get("user_permissions:{$this->user->id}"));
        $this->assertNotNull(Cache::get("user_permissions:{$user2->id}"));
    }

    /**
     * Test that the service manager handles users with no permissions gracefully
     */
    public function test_handles_users_with_no_permissions_gracefully()
    {
        // User has no groups or permissions

        // Test that userHasPermission returns false
        $this->assertFalse($this->serviceManager->userHasPermission($this->user->id, 'any-permission'));

        // Test that getUserPermissions returns empty array
        $permissions = $this->serviceManager->getUserPermissions($this->user->id);
        $this->assertIsArray($permissions);
        $this->assertEmpty($permissions);

        // Test that cache can still be warmed up
        $this->serviceManager->warmUpUserCache($this->user->id);
        $this->assertNotNull(Cache::get("user_permissions:{$this->user->id}"));

        // Verify cached permissions are empty
        $cachedPermissions = Cache::get("user_permissions:{$this->user->id}");
        $this->assertIsArray($cachedPermissions);
        $this->assertEmpty($cachedPermissions);
    }
}
