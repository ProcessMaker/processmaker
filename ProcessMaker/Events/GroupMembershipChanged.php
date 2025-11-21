<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;

class GroupMembershipChanged
{
    use Dispatchable, SerializesModels;

    public ?Group $group;

    public ?Group $parentGroup;

    public string $action; // 'added', 'removed', 'updated'

    public ?GroupMember $groupMember;

    /**
     * Create a new event instance.
     */
    public function __construct(Group $group, ?Group $parentGroup, string $action, ?GroupMember $groupMember = null)
    {
        $this->group = $group;
        $this->parentGroup = $parentGroup;
        $this->action = $action;
        $this->groupMember = $groupMember;
    }

    /**
     * Get the group that was affected
     */
    public function getGroup(): ?Group
    {
        return $this->group;
    }

    /**
     * Get the parent group (if any)
     */
    public function getParentGroup(): ?Group
    {
        return $this->parentGroup;
    }

    /**
     * Get the action performed
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Get the group member record
     */
    public function getGroupMember(): ?GroupMember
    {
        return $this->groupMember;
    }

    /**
     * Check if this is a removal action
     */
    public function isRemoval(): bool
    {
        return $this->action === 'removed';
    }

    /**
     * Check if this is an addition action
     */
    public function isAddition(): bool
    {
        return $this->action === 'added';
    }

    /**
     * Check if this is an update action
     */
    public function isUpdate(): bool
    {
        return $this->action === 'updated';
    }
}
