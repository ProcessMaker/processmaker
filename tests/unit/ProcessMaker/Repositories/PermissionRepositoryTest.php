<?php

namespace Tests\Unit\ProcessMaker\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\User;
use ProcessMaker\Repositories\PermissionRepository;
use Tests\TestCase;

class PermissionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private PermissionRepository $repository;

    private User $user;

    private Group $group1;

    private Group $group2;

    private Group $group3;

    private Permission $permission1;

    private Permission $permission2;

    private Permission $permission3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new PermissionRepository();

        // Create test permissions
        $this->permission1 = Permission::factory()->create(['name' => 'permission-1']);
        $this->permission2 = Permission::factory()->create(['name' => 'permission-2']);
        $this->permission3 = Permission::factory()->create(['name' => 'permission-3']);

        // Create test groups
        $this->group1 = Group::factory()->create(['name' => 'Group 1']);
        $this->group2 = Group::factory()->create(['name' => 'Group 2']);
        $this->group3 = Group::factory()->create(['name' => 'Group 3']);

        // Create test user
        $this->user = User::factory()->create(['username' => 'testuser']);
    }

    /**
     * Test that user permissions include direct permissions
     */
    public function test_user_permissions_include_direct_permissions()
    {
        // Add direct permission to user
        $this->user->permissions()->attach($this->permission1->id);

        // Get user permissions
        $permissions = $this->repository->getUserPermissions($this->user->id);

        // Assert that direct permission is included
        $this->assertContains('permission-1', $permissions);
    }

    /**
     * Test that user permissions include group permissions
     */
    public function test_user_permissions_include_group_permissions()
    {
        // Add user to group
        $this->user->groupMembersFromMemberable()->create([
            'group_id' => $this->group1->id,
            'member_id' => $this->user->id,
            'member_type' => User::class,
        ]);

        // Add permission to group
        $this->group1->permissions()->attach($this->permission1->id);

        // Get user permissions
        $permissions = $this->repository->getUserPermissions($this->user->id);

        // Assert that group permission is included
        $this->assertContains('permission-1', $permissions);
    }

    /**
     * Test hierarchical inheritance: User → Group1 → Group2
     */
    public function test_hierarchical_inheritance_two_levels()
    {
        // Set up hierarchy: User → Group1 → Group2
        $this->user->groupMembersFromMemberable()->create([
            'group_id' => $this->group1->id,
            'member_id' => $this->user->id,
            'member_type' => User::class,
        ]);

        $this->group1->groupMembersFromMemberable()->create([
            'group_id' => $this->group2->id,
            'member_id' => $this->group1->id,
            'member_type' => Group::class,
        ]);

        // Add permissions to different levels
        $this->group1->permissions()->attach($this->permission1->id);
        $this->group2->permissions()->attach($this->permission2->id);

        // Get user permissions
        $permissions = $this->repository->getUserPermissions($this->user->id);

        // Assert that both permissions are inherited
        $this->assertContains('permission-1', $permissions);
        $this->assertContains('permission-2', $permissions);
    }

    /**
     * Test hierarchical inheritance: User → Group1 → Group2 → Group3
     */
    public function test_hierarchical_inheritance_three_levels()
    {
        // Set up hierarchy: User → Group1 → Group2 → Group3
        $this->user->groupMembersFromMemberable()->create([
            'group_id' => $this->group1->id,
            'member_id' => $this->user->id,
            'member_type' => User::class,
        ]);

        $this->group1->groupMembersFromMemberable()->create([
            'group_id' => $this->group2->id,
            'member_id' => $this->group1->id,
            'member_type' => Group::class,
        ]);

        $this->group2->groupMembersFromMemberable()->create([
            'group_id' => $this->group3->id,
            'member_id' => $this->group2->id,
            'member_type' => Group::class,
        ]);

        // Add permissions to different levels
        $this->group1->permissions()->attach($this->permission1->id);
        $this->group2->permissions()->attach($this->permission2->id);
        $this->group3->permissions()->attach($this->permission3->id);

        // Get user permissions
        $permissions = $this->repository->getUserPermissions($this->user->id);

        // Assert that all permissions are inherited
        $this->assertContains('permission-1', $permissions);
        $this->assertContains('permission-2', $permissions);
        $this->assertContains('permission-3', $permissions);
    }

    /**
     * Test that userHasPermission works with hierarchical inheritance
     */
    public function test_user_has_permission_with_hierarchical_inheritance()
    {
        // Set up hierarchy: User → Group1 → Group2
        $this->user->groupMembersFromMemberable()->create([
            'group_id' => $this->group1->id,
            'member_id' => $this->user->id,
            'member_type' => User::class,
        ]);

        $this->group1->groupMembersFromMemberable()->create([
            'group_id' => $this->group2->id,
            'member_id' => $this->group1->id,
            'member_type' => Group::class,
        ]);

        // Add permission to Group2 (inherited through Group1)
        $this->group2->permissions()->attach($this->permission1->id);

        // Test that user has the inherited permission
        $this->assertTrue($this->repository->userHasPermission($this->user->id, 'permission-1'));
        $this->assertFalse($this->repository->userHasPermission($this->user->id, 'non-existent-permission'));
    }

    /**
     * Test that getNestedGroupPermissions includes nested group permissions
     */
    public function test_get_group_permissions_includes_nested_permissions()
    {
        // Set up hierarchy: Group1 → Group2 → Group3
        $this->group1->groupMembersFromMemberable()->create([
            'group_id' => $this->group2->id,
            'member_id' => $this->group1->id,
            'member_type' => Group::class,
        ]);

        $this->group2->groupMembersFromMemberable()->create([
            'group_id' => $this->group3->id,
            'member_id' => $this->group2->id,
            'member_type' => Group::class,
        ]);

        // Add permissions to different levels
        $this->group1->permissions()->attach($this->permission1->id);
        $this->group2->permissions()->attach($this->permission2->id);
        $this->group3->permissions()->attach($this->permission3->id);

        // Get Group1 permissions (should include nested)
        $permissions = $this->repository->getNestedGroupPermissions($this->group1->id);

        // Assert that all permissions are included
        $this->assertContains('permission-1', $permissions);
        $this->assertContains('permission-2', $permissions);
        $this->assertContains('permission-3', $permissions);
    }

    /**
     * Test that permissions are not duplicated in inheritance
     */
    public function test_permissions_are_not_duplicated_in_inheritance()
    {
        // Set up hierarchy: User → Group1 → Group2
        $this->user->groupMembersFromMemberable()->create([
            'group_id' => $this->group1->id,
            'member_id' => $this->user->id,
            'member_type' => User::class,
        ]);

        $this->group1->groupMembersFromMemberable()->create([
            'group_id' => $this->group2->id,
            'member_id' => $this->group1->id,
            'member_type' => Group::class,
        ]);

        // Add the same permission to both groups
        $this->group1->permissions()->attach($this->permission1->id);
        $this->group2->permissions()->attach($this->permission1->id);

        // Get user permissions
        $permissions = $this->repository->getUserPermissions($this->user->id);

        // Assert that permission appears only once
        $this->assertCount(1, array_filter($permissions, fn ($p) => $p === 'permission-1'));
    }

    /**
     * Test that complex hierarchies work correctly
     */
    public function test_complex_hierarchies_work_correctly()
    {
        // Create additional groups for complex hierarchy
        $group4 = Group::factory()->create(['name' => 'Group 4']);
        $group5 = Group::factory()->create(['name' => 'Group 5']);

        // Set up complex hierarchy: User → Group1 → Group2 → Group3
        //                                    ↓
        //                                 Group4 → Group5
        $this->user->groupMembersFromMemberable()->create([
            'group_id' => $this->group1->id,
            'member_id' => $this->user->id,
            'member_type' => User::class,
        ]);

        $this->group1->groupMembersFromMemberable()->create([
            'group_id' => $this->group2->id,
            'member_id' => $this->group1->id,
            'member_type' => Group::class,
        ]);

        $this->group1->groupMembersFromMemberable()->create([
            'group_id' => $group4->id,
            'member_id' => $this->group1->id,
            'member_type' => Group::class,
        ]);

        $this->group2->groupMembersFromMemberable()->create([
            'group_id' => $this->group3->id,
            'member_id' => $this->group2->id,
            'member_type' => Group::class,
        ]);

        $group4->groupMembersFromMemberable()->create([
            'group_id' => $group5->id,
            'member_id' => $group4->id,
            'member_type' => Group::class,
        ]);

        // Add permissions to different levels
        $this->group1->permissions()->attach($this->permission1->id);
        $this->group2->permissions()->attach($this->permission2->id);
        $this->group3->permissions()->attach($this->permission3->id);
        $group4->permissions()->attach($this->permission1->id); // Same as group1
        $group5->permissions()->attach($this->permission2->id); // Same as group2

        // Get user permissions
        $permissions = $this->repository->getUserPermissions($this->user->id);

        // Assert that all unique permissions are inherited
        $this->assertContains('permission-1', $permissions);
        $this->assertContains('permission-2', $permissions);
        $this->assertContains('permission-3', $permissions);

        // Assert that permissions are not duplicated
        $this->assertCount(3, array_unique($permissions));
    }
}
