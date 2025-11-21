<?php

namespace Tests\Unit\ProcessMaker\Observers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use ProcessMaker\Events\GroupMembershipChanged;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\User;
use ProcessMaker\Observers\GroupMemberObserver;
use Tests\TestCase;

class GroupMemberObserverTest extends TestCase
{
    use RefreshDatabase;

    private GroupMemberObserver $observer;

    private Group $group;

    private Group $parentGroup;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->observer = new GroupMemberObserver();

        // Create test groups
        $this->group = Group::factory()->create(['name' => 'Test Group']);
        $this->parentGroup = Group::factory()->create(['name' => 'Parent Group']);

        // Create test user
        $this->user = User::factory()->create(['username' => 'testuser']);

        // Fake events to test if they are dispatched
        Event::fake();
    }

    /**
     * Test that GroupMembershipChanged event is dispatched when a group is added to another group
     */
    public function test_dispatches_event_when_group_added_to_group()
    {
        // Create a group member relationship (group added to parent group)
        $groupMember = new GroupMember([
            'group_id' => $this->parentGroup->id,
            'member_id' => $this->group->id,
            'member_type' => Group::class,
        ]);

        // Trigger the created event
        $this->observer->created($groupMember);

        // Assert that the event was dispatched
        Event::assertDispatched(GroupMembershipChanged::class, function ($event) use ($groupMember) {
            return $event->getGroup()->id === $this->group->id &&
                   $event->getParentGroup()->id === $this->parentGroup->id &&
                   $event->getAction() === 'added' &&
                   $event->getGroupMember()->id === $groupMember->id;
        });
    }

    /**
     * Test that GroupMembershipChanged event is dispatched when a group is removed from another group
     */
    public function test_dispatches_event_when_group_removed_from_group()
    {
        // Create a group member relationship
        $groupMember = GroupMember::factory()->create([
            'group_id' => $this->parentGroup->id,
            'member_id' => $this->group->id,
            'member_type' => Group::class,
        ]);

        // Trigger the deleted event
        $this->observer->deleted($groupMember);

        // Assert that the event was dispatched
        Event::assertDispatched(GroupMembershipChanged::class, function ($event) {
            return $event->getGroup()->id === $this->group->id &&
                   $event->getParentGroup()->id === $this->parentGroup->id &&
                   $event->getAction() === 'removed';
        });
    }

    /**
     * Test that GroupMembershipChanged event is dispatched when a group membership is updated
     */
    public function test_dispatches_event_when_group_membership_updated()
    {
        // Create a group member relationship
        $groupMember = GroupMember::factory()->create([
            'group_id' => $this->parentGroup->id,
            'member_id' => $this->group->id,
            'member_type' => Group::class,
        ]);

        // Trigger the updated event
        $this->observer->updated($groupMember);

        // Assert that the event was dispatched
        Event::assertDispatched(GroupMembershipChanged::class, function ($event) {
            return $event->getGroup()->id === $this->group->id &&
                   $event->getParentGroup()->id === $this->parentGroup->id &&
                   $event->getAction() === 'updated';
        });
    }

    /**
     * Test that event is not dispatched for user memberships (only group memberships)
     */
    public function test_does_not_dispatch_event_for_user_memberships()
    {
        // Create a user member relationship
        $userMember = new GroupMember([
            'group_id' => $this->group->id,
            'member_id' => $this->user->id,
            'member_type' => User::class,
        ]);

        // Trigger the created event
        $this->observer->created($userMember);

        // Assert that no GroupMembershipChanged event was dispatched
        Event::assertNotDispatched(GroupMembershipChanged::class);
    }

    /**
     * Test that event is not dispatched for invalid group relationships
     */
    public function test_does_not_dispatch_event_for_invalid_group_relationships()
    {
        // Create a group member with non-existent group
        $groupMember = new GroupMember([
            'group_id' => 99999, // Non-existent group ID
            'member_id' => $this->group->id,
            'member_type' => Group::class,
        ]);

        // Trigger the created event
        $this->observer->created($groupMember);

        // Assert that no GroupMembershipChanged event was dispatched
        Event::assertNotDispatched(GroupMembershipChanged::class);
    }

    /**
     * Test that event is not dispatched for invalid member groups
     */
    public function test_does_not_dispatch_event_for_invalid_member_groups()
    {
        // Create a group member with non-existent member group
        $groupMember = new GroupMember([
            'group_id' => $this->parentGroup->id,
            'member_id' => 99999, // Non-existent member group ID
            'member_type' => Group::class,
        ]);

        // Trigger the created event
        $this->observer->created($groupMember);

        // Assert that no GroupMembershipChanged event was dispatched
        Event::assertNotDispatched(GroupMembershipChanged::class);
    }

    /**
     * Test that multiple events can be dispatched for different actions
     */
    public function test_can_dispatch_multiple_events_for_different_actions()
    {
        // Create a group member relationship
        $groupMember = GroupMember::factory()->create([
            'group_id' => $this->parentGroup->id,
            'member_id' => $this->group->id,
            'member_type' => Group::class,
        ]);

        // Trigger multiple events (excluding restored since soft deletes are disabled)
        $this->observer->created($groupMember);
        $this->observer->updated($groupMember);
        $this->observer->deleted($groupMember);

        // Assert that all events were dispatched (3 events: created, updated, deleted)
        Event::assertDispatched(GroupMembershipChanged::class, 3);

        // Check specific events
        Event::assertDispatched(GroupMembershipChanged::class, function ($event) {
            return $event->getAction() === 'added';
        });

        Event::assertDispatched(GroupMembershipChanged::class, function ($event) {
            return $event->getAction() === 'updated';
        });

        Event::assertDispatched(GroupMembershipChanged::class, function ($event) {
            return $event->getAction() === 'removed';
        });
    }
}
