<?php

namespace Tests\Unit\ProcessMaker\Events;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Events\GroupMembershipChanged;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use Tests\TestCase;

class GroupMembershipChangedTest extends TestCase
{
    use RefreshDatabase;

    private Group $group;

    private Group $parentGroup;

    private GroupMember $groupMember;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test groups
        $this->group = Group::factory()->create(['name' => 'Test Group']);
        $this->parentGroup = Group::factory()->create(['name' => 'Parent Group']);

        // Create a group member relationship
        $this->groupMember = GroupMember::factory()->create([
            'group_id' => $this->parentGroup->id,
            'member_id' => $this->group->id,
            'member_type' => Group::class,
        ]);
    }

    /**
     * Test event creation with all parameters
     */
    public function test_event_creation_with_all_parameters()
    {
        $event = new GroupMembershipChanged(
            $this->group,
            $this->parentGroup,
            'added',
            $this->groupMember
        );

        $this->assertInstanceOf(GroupMembershipChanged::class, $event);
        $this->assertEquals($this->group->id, $event->getGroup()->id);
        $this->assertEquals($this->parentGroup->id, $event->getParentGroup()->id);
        $this->assertEquals('added', $event->getAction());
        $this->assertEquals($this->groupMember->id, $event->getGroupMember()->id);
    }

    /**
     * Test event creation without group member
     */
    public function test_event_creation_without_group_member()
    {
        $event = new GroupMembershipChanged(
            $this->group,
            $this->parentGroup,
            'removed'
        );

        $this->assertInstanceOf(GroupMembershipChanged::class, $event);
        $this->assertEquals($this->group->id, $event->getGroup()->id);
        $this->assertEquals($this->parentGroup->id, $event->getParentGroup()->id);
        $this->assertEquals('removed', $event->getAction());
        $this->assertNull($event->getGroupMember());
    }

    /**
     * Test event creation with null parent group
     */
    public function test_event_creation_with_null_parent_group()
    {
        $event = new GroupMembershipChanged(
            $this->group,
            null,
            'updated'
        );

        $this->assertInstanceOf(GroupMembershipChanged::class, $event);
        $this->assertEquals($this->group->id, $event->getGroup()->id);
        $this->assertNull($event->getParentGroup());
        $this->assertEquals('updated', $event->getAction());
    }

    /**
     * Test action checking methods
     */
    public function test_action_checking_methods()
    {
        $addedEvent = new GroupMembershipChanged($this->group, $this->parentGroup, 'added');
        $removedEvent = new GroupMembershipChanged($this->group, $this->parentGroup, 'removed');
        $updatedEvent = new GroupMembershipChanged($this->group, $this->parentGroup, 'updated');

        // Test isAddition method
        $this->assertTrue($addedEvent->isAddition());
        $this->assertFalse($removedEvent->isAddition());
        $this->assertFalse($updatedEvent->isAddition());

        // Test isRemoval method
        $this->assertFalse($addedEvent->isRemoval());
        $this->assertTrue($removedEvent->isRemoval());
        $this->assertFalse($updatedEvent->isRemoval());

        // Test isUpdate method
        $this->assertFalse($addedEvent->isUpdate());
        $this->assertFalse($removedEvent->isUpdate());
        $this->assertTrue($updatedEvent->isUpdate());
    }

    /**
     * Test event serialization
     */
    public function test_event_serialization()
    {
        $event = new GroupMembershipChanged(
            $this->group,
            $this->parentGroup,
            'added',
            $this->groupMember
        );

        // Test that the event can be serialized (for queue processing)
        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(GroupMembershipChanged::class, $unserialized);
        $this->assertEquals($event->getAction(), $unserialized->getAction());
        $this->assertEquals($event->getGroup()->id, $unserialized->getGroup()->id);
        $this->assertEquals($event->getParentGroup()->id, $unserialized->getParentGroup()->id);
    }

    /**
     * Test event with different action types
     */
    public function test_event_with_different_action_types()
    {
        $actions = ['added', 'removed', 'updated', 'restored'];

        foreach ($actions as $action) {
            $event = new GroupMembershipChanged($this->group, $this->parentGroup, $action);

            $this->assertEquals($action, $event->getAction());
            $this->assertEquals($this->group->id, $event->getGroup()->id);
            $this->assertEquals($this->parentGroup->id, $event->getParentGroup()->id);
        }
    }

    /**
     * Test event properties are accessible
     */
    public function test_event_properties_are_accessible()
    {
        $event = new GroupMembershipChanged(
            $this->group,
            $this->parentGroup,
            'added',
            $this->groupMember
        );

        // Test public properties are accessible
        $this->assertEquals($this->group->id, $event->group->id);
        $this->assertEquals($this->parentGroup->id, $event->parentGroup->id);
        $this->assertEquals('added', $event->action);
        $this->assertEquals($this->groupMember->id, $event->groupMember->id);
    }
}
