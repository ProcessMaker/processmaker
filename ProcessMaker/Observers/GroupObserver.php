<?php

namespace ProcessMaker\Observers;

use ProcessMaker\Models\Group;

class GroupObserver
{
    /**
     * DB cascade deletes group_members without firing GroupMember events.
     */
    public function deleting(Group $group): void
    {
        Group::bumpSelfServiceHierarchyVersion();
    }
}
