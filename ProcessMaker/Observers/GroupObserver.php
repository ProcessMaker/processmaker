<?php

namespace ProcessMaker\Observers;

use ProcessMaker\Models\Group;

class GroupObserver
{
    /**
     * DB cascade deletes group_members without firing GroupMember events, and the
     * cascade is only visible once the DELETE has run. Bump after the delete so a
     * cache fill that raced the delete cannot be reused under the new version.
     */
    public function deleted(): void
    {
        Group::bumpSelfServiceHierarchyVersion();
    }
}
