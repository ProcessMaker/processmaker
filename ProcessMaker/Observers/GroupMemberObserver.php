<?php

namespace ProcessMaker\Observers;

use Illuminate\Support\Facades\Log;
use ProcessMaker\Events\GroupMembershipChanged;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;

class GroupMemberObserver
{
    /**
     * Handle the GroupMember "created" event.
     */
    public function created(GroupMember $groupMember): void
    {
        // Only handle group-to-group relationships, not user-to-group
        if ($groupMember->member_type === Group::class) {
            $group = Group::find($groupMember->member_id);
            $parentGroup = Group::find($groupMember->group_id);

            if ($group && $parentGroup) {
                Log::info("Group {$group->name} (ID: {$group->id}) added to group {$parentGroup->name} (ID: {$parentGroup->id})");

                event(new GroupMembershipChanged($group, $parentGroup, 'added', $groupMember));
            }
        }
    }

    /**
     * Handle the GroupMember "updated" event.
     */
    public function updated(GroupMember $groupMember): void
    {
        // Only handle group-to-group relationships, not user-to-group
        if ($groupMember->member_type === Group::class) {
            $group = Group::find($groupMember->member_id);
            $parentGroup = Group::find($groupMember->group_id);

            if ($group && $parentGroup) {
                Log::info("Group {$group->name} (ID: {$group->id}) membership updated in group {$parentGroup->name} (ID: {$parentGroup->id})");

                event(new GroupMembershipChanged($group, $parentGroup, 'updated', $groupMember));
            }
        }
    }

    /**
     * Handle the GroupMember "deleted" event.
     */
    public function deleted(GroupMember $groupMember): void
    {
        // Only handle group-to-group relationships, not user-to-group
        if ($groupMember->member_type === Group::class) {
            $group = Group::find($groupMember->member_id);
            $parentGroup = Group::find($groupMember->group_id);

            if ($group && $parentGroup) {
                Log::info("Group {$group->name} (ID: {$group->id}) removed from group {$parentGroup->name} (ID: {$parentGroup->id})");

                event(new GroupMembershipChanged($group, $parentGroup, 'removed', $groupMember));
            }
        }
    }

    /**
     * Handle the GroupMember "restored" event.
     */
    public function restored(GroupMember $groupMember): void
    {
        // Only handle group-to-group relationships, not user-to-group
        if ($groupMember->member_type === Group::class) {
            $group = Group::find($groupMember->member_id);
            $parentGroup = Group::find($groupMember->group_id);

            if ($group && $parentGroup) {
                Log::info("Group {$group->name} (ID: {$group->id}) restored to group {$parentGroup->name} (ID: {$parentGroup->id})");

                event(new GroupMembershipChanged($group, $parentGroup, 'added', $groupMember));
            }
        }
    }
}
