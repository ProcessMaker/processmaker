<?php

namespace ProcessMaker\Models;

use ProcessMaker\Models\Group;
use Tests\TestCase;

class GroupTest extends TestCase
{
    /**
     * Test group without manager.
     *
     * @return void
     */
    public function testGroupWithManager()
    {
        $manager = User::factory()->create();
        $group = Group::factory()->create(['manager_id' => $manager->id]);
        $this->assertInstanceOf(Group::class, $group);
        $this->assertInstanceOf(User::class, $group->manager);
        $this->assertEquals($group->manager_id, $group->manager->id);
    }

    /**
     * Test group without manager.
     *
     * @return void
     */
    public function testGroupWithoutManager()
    {
        $group = Group::factory()->make([
            'manager_id' => null,
        ]);
        $this->assertInstanceOf(Group::class, $group);
        $this->assertNull($group->manager);
        $this->assertNull($group->manager_id);
    }

    public function testAncestorIdsForReturnsParentGroupsInHierarchy()
    {
        $parentGroup = Group::factory()->create(['name' => 'Parent Group']);
        $childGroup = Group::factory()->create(['name' => 'Child Group']);

        $childGroup->groupMembersFromMemberable()->create([
            'group_id' => $parentGroup->id,
            'member_id' => $childGroup->id,
            'member_type' => Group::class,
        ]);

        $ancestors = Group::ancestorIdsFor([$childGroup->id]);

        $this->assertEquals([$parentGroup->id], $ancestors->all());
    }

    public function testAncestorIdsForHandlesCircularGroupReferences()
    {
        $group0 = Group::factory()->create(['name' => 'Group 0']);
        $group1 = Group::factory()->create(['name' => 'Group 1']);

        // Group0 is a member of Group1, and Group1 is a member of Group0.
        $group0->groupMembersFromMemberable()->create([
            'group_id' => $group1->id,
            'member_id' => $group0->id,
            'member_type' => Group::class,
        ]);
        $group1->groupMembersFromMemberable()->create([
            'group_id' => $group0->id,
            'member_id' => $group1->id,
            'member_type' => Group::class,
        ]);

        $ancestors = Group::ancestorIdsFor([$group0->id]);

        $this->assertEquals([$group1->id], $ancestors->all());
    }
}
