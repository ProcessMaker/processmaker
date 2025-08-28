<?php

namespace Tests\Unit\ProcessMaker\Listeners;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Events\GroupMembershipChanged;
use ProcessMaker\Listeners\InvalidatePermissionCacheOnGroupHierarchyChange;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\User;
use Tests\TestCase;

class InvalidatePermissionCacheOnGroupHierarchyChangeTest extends TestCase
{
    use RefreshDatabase;

    private InvalidatePermissionCacheOnGroupHierarchyChange $listener;

    private Group $group;

    private Group $parentGroup;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the listener with a real permission service
        $this->listener = new InvalidatePermissionCacheOnGroupHierarchyChange(
            app(\ProcessMaker\Services\PermissionServiceManager::class)
        );

        // Create test data
        $this->createTestData();

        // Clear cache
        Cache::flush();
    }

    /**
     * Create test data for the tests
     */
    private function createTestData(): void
    {
        // Create permissions
        $permission = Permission::factory()->create(['name' => 'test-permission']);

        // Create groups
        $this->group = Group::factory()->create(['name' => 'Child Group']);
        $this->parentGroup = Group::factory()->create(['name' => 'Parent Group']);

        // Create user
        $this->user = User::factory()->create(['username' => 'testuser']);

        // Set up hierarchy: User → Group → ParentGroup
        $this->user->groupMembersFromMemberable()->create([
            'group_id' => $this->group->id,
            'member_id' => $this->user->id,
            'member_type' => User::class,
        ]);

        $this->group->groupMembersFromMemberable()->create([
            'group_id' => $this->parentGroup->id,
            'member_id' => $this->group->id,
            'member_type' => Group::class,
        ]);

        // Add permission to parent group
        $this->parentGroup->permissions()->attach($permission->id);
    }

    /**
     * Test that the listener can handle events without throwing type errors
     */
    public function test_listener_handles_events_without_type_errors()
    {
        // Test all action types
        $actions = ['added', 'removed', 'updated', 'restored'];

        foreach ($actions as $action) {
            // Create the event
            $event = new GroupMembershipChanged($this->group, $this->parentGroup, $action);

            // Handle the event - should not throw any exceptions or type errors
            $this->expectNotToPerformAssertions();
            $this->listener->handle($event);
        }
    }

    /**
     * Test that circular references are handled safely
     */
    public function test_handles_circular_references_safely()
    {
        // Create a circular reference: Group A → Group B → Group A
        $groupA = Group::factory()->create(['name' => 'Group A']);
        $groupB = Group::factory()->create(['name' => 'Group B']);

        // Create circular relationship
        $groupA->groupMembersFromMemberable()->create([
            'group_id' => $groupB->id,
            'member_id' => $groupA->id,
            'member_type' => Group::class,
        ]);

        $groupB->groupMembersFromMemberable()->create([
            'group_id' => $groupA->id,
            'member_id' => $groupB->id,
            'member_type' => Group::class,
        ]);

        // Create the event
        $event = new GroupMembershipChanged($groupA, $groupB, 'removed');

        // Handle the event - should not cause infinite recursion or type errors
        $this->expectNotToPerformAssertions();
        $this->listener->handle($event);
    }

    /**
     * Test that deep hierarchies are handled safely
     */
    public function test_handles_deep_hierarchies_safely()
    {
        // Create a deep hierarchy: Group1 → Group2 → Group3 → Group4 → Group5
        $groups = [];
        for ($i = 1; $i <= 5; $i++) {
            $groups[$i] = Group::factory()->create(['name' => "Group {$i}"]);
        }

        // Create deep hierarchy
        for ($i = 1; $i < 5; $i++) {
            $groups[$i]->groupMembersFromMemberable()->create([
                'group_id' => $groups[$i + 1]->id,
                'member_id' => $groups[$i]->id,
                'member_type' => Group::class,
            ]);
        }

        // Create the event for the top group
        $event = new GroupMembershipChanged($groups[1], $groups[2], 'removed');

        // Handle the event - should not cause infinite recursion or type errors
        $this->expectNotToPerformAssertions();
        $this->listener->handle($event);
    }

    /**
     * Test that the listener handles missing groups gracefully
     */
    public function test_handles_missing_groups_gracefully()
    {
        // Create an event with a non-existent group
        $nonExistentGroup = new Group(['id' => 99999, 'name' => 'Non-existent Group']);
        $event = new GroupMembershipChanged($nonExistentGroup, $this->parentGroup, 'removed');

        // Handle the event - should not throw any exceptions or type errors
        $this->expectNotToPerformAssertions();
        $this->listener->handle($event);
    }

    /**
     * Test that the listener can handle events with null parent group
     */
    public function test_handles_events_with_null_parent_group()
    {
        // Create the event with null parent group
        $event = new GroupMembershipChanged($this->group, null, 'updated');

        // Handle the event - should not throw any exceptions or type errors
        $this->expectNotToPerformAssertions();
        $this->listener->handle($event);
    }
}
