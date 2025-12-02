<?php

namespace Tests\Unit\ProcessMaker\Traits;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\User;
use Tests\TestCase;

class HasAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Group $group;

    private Permission $permission;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->permission = Permission::factory()->create(['name' => 'test-permission']);
        $this->group = Group::factory()->create(['name' => 'Test Group']);
        $this->user = User::factory()->create(['username' => 'testuser']);
    }

    /**
     * Test that hasPermission delegates to PermissionServiceManager
     */
    public function test_has_permission_delegates_to_permission_service_manager()
    {
        // Add user to group with permission
        $this->user->groupMembersFromMemberable()->create([
            'group_id' => $this->group->id,
            'member_id' => $this->user->id,
            'member_type' => User::class,
        ]);
        $this->group->permissions()->attach($this->permission->id);

        // Test that user has the permission
        $this->assertTrue($this->user->hasPermission('test-permission'));
        $this->assertFalse($this->user->hasPermission('non-existent-permission'));
    }

    /**
     * Test that loadPermissions works correctly
     */
    public function test_load_permissions_works_correctly()
    {
        // Add user to group with permission
        $this->user->groupMembersFromMemberable()->create([
            'group_id' => $this->group->id,
            'member_id' => $this->user->id,
            'member_type' => User::class,
        ]);
        $this->group->permissions()->attach($this->permission->id);

        // Load permissions
        $this->user->loadPermissions();

        // Test that permissions were loaded
        $this->assertTrue($this->user->hasPermission('test-permission'));
    }

    /**
     * Test that invalidatePermissionCache works correctly
     */
    public function test_invalidate_permission_cache_works_correctly()
    {
        // Add user to group with permission
        $this->user->groupMembersFromMemberable()->create([
            'group_id' => $this->group->id,
            'member_id' => $this->user->id,
            'member_type' => User::class,
        ]);
        $this->group->permissions()->attach($this->permission->id);

        // Test that user has the permission initially
        $this->assertTrue($this->user->hasPermission('test-permission'));

        // Invalidate cache
        $this->user->invalidatePermissionCache();

        // Test that user still has the permission after cache invalidation
        $this->assertTrue($this->user->hasPermission('test-permission'));
    }

    /**
     * Test that cache invalidation actually works when permissions change
     */
    public function test_cache_invalidation_works_when_permissions_change()
    {
        // Add user to group with permission
        $this->user->groupMembersFromMemberable()->create([
            'group_id' => $this->group->id,
            'member_id' => $this->user->id,
            'member_type' => User::class,
        ]);
        $this->group->permissions()->attach($this->permission->id);

        // Test that user has the permission initially
        $this->assertTrue($this->user->hasPermission('test-permission'));

        // Remove the permission from the group
        $this->group->permissions()->detach($this->permission->id);

        // Test that user still has the permission (due to caching)
        $this->assertTrue($this->user->hasPermission('test-permission'));

        // Invalidate cache`
        $this->user->invalidatePermissionCache();

        // Test that user no longer has the permission after cache invalidation
        $this->assertFalse($this->user->hasPermission('test-permission'));
    }

    /**
     * Test that hierarchical inheritance works through the trait
     */
    public function test_hierarchical_inheritance_works_through_trait()
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

        // Test that user has the inherited permission through the trait
        $this->assertTrue($this->user->hasPermission('test-permission'));
    }

    /**
     * Test that the trait handles users with no permissions gracefully
     */
    public function test_handles_users_with_no_permissions_gracefully()
    {
        // User has no groups or permissions

        // Test that hasPermission returns false
        $this->assertFalse($this->user->hasPermission('any-permission'));

        // Test that loadPermissions doesn't throw errors
        $this->user->loadPermissions();

        // Test that invalidatePermissionCache doesn't throw errors
        $this->user->invalidatePermissionCache();
    }

    /**
     * Test that the trait works with multiple permissions
     */
    public function test_works_with_multiple_permissions()
    {
        // Create additional permissions
        $permission2 = Permission::factory()->create(['name' => 'permission-2']);
        $permission3 = Permission::factory()->create(['name' => 'permission-3']);

        // Add user to group with multiple permissions
        $this->user->groupMembersFromMemberable()->create([
            'group_id' => $this->group->id,
            'member_id' => $this->user->id,
            'member_type' => User::class,
        ]);

        $this->group->permissions()->attach([
            $this->permission->id,
            $permission2->id,
            $permission3->id,
        ]);

        // Test that user has all permissions
        $this->assertTrue($this->user->hasPermission('test-permission'));
        $this->assertTrue($this->user->hasPermission('permission-2'));
        $this->assertTrue($this->user->hasPermission('permission-3'));
        $this->assertFalse($this->user->hasPermission('non-existent-permission'));
    }

    /**
     * Test that the trait works with direct user permissions
     */
    public function test_works_with_direct_user_permissions()
    {
        // Add permission directly to user
        $this->user->permissions()->attach($this->permission->id);

        // Test that user has the direct permission
        $this->assertTrue($this->user->hasPermission('test-permission'));

        // Test that loadPermissions still works
        $this->user->loadPermissions();
        $this->assertTrue($this->user->hasPermission('test-permission'));
    }

    /**
     * Test that the trait works with both direct and group permissions
     */
    public function test_works_with_both_direct_and_group_permissions()
    {
        // Create additional permission
        $permission2 = Permission::factory()->create(['name' => 'permission-2']);

        // Add permission directly to user
        $this->user->permissions()->attach($this->permission->id);

        // Add user to group with different permission
        $this->user->groupMembersFromMemberable()->create([
            'group_id' => $this->group->id,
            'member_id' => $this->user->id,
            'member_type' => User::class,
        ]);
        $this->group->permissions()->attach($permission2->id);

        // Test that user has both permissions
        $this->assertTrue($this->user->hasPermission('test-permission'));
        $this->assertTrue($this->user->hasPermission('permission-2'));

        // Test that loadPermissions works
        $this->user->loadPermissions();
        $this->assertTrue($this->user->hasPermission('test-permission'));
        $this->assertTrue($this->user->hasPermission('permission-2'));
    }
}
