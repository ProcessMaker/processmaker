<?php

namespace ProcessMaker\Listeners;

use Illuminate\Support\Facades\Log;
use ProcessMaker\Events\GroupMembershipChanged;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;
use ProcessMaker\Services\PermissionServiceManager;

class InvalidatePermissionCacheOnGroupHierarchyChange
{
    private PermissionServiceManager $permissionService;

    public function __construct(PermissionServiceManager $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Handle the event.
     */
    public function handle(GroupMembershipChanged $event): void
    {
        try {
            $group = $event->getGroup();
            $action = $event->getAction();

            // All actions (added, removed, updated) require the same cache invalidation logic
            // because they all affect the permission hierarchy for the group and its descendants
            $this->invalidateCacheForGroupAndDescendants($group);
            Log::info("Successfully invalidated permission cache for group hierarchy change: {$action} for group {$group->id}");
        } catch (\Exception $e) {
            Log::error('Failed to invalidate permission cache on group hierarchy change', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'group_id' => $event->getGroup()->id ?? 'unknown',
                'action' => $event->getAction(),
            ]);
            throw $e; // Re-throw to ensure error is properly handled
        }
    }

    /**
     * Invalidate cache for a group and all its descendant groups
     */
    private function invalidateCacheForGroupAndDescendants(Group $group): void
    {
        // Get all users directly in this group
        $this->invalidateCacheForUsersInGroup($group);

        // Get all descendant groups (groups that inherit from this group)
        $descendantGroups = $this->getDescendantGroups($group);

        foreach ($descendantGroups as $descendantGroup) {
            $this->invalidateCacheForUsersInGroup($descendantGroup);
        }

        Log::info("Invalidated cache for group {$group->id} and {$descendantGroups->count()} descendant groups");
    }

    /**
     * Get all descendant groups that inherit from a given group
     */
    private function getDescendantGroups(Group $group, array $visitedGroups = [], int $maxDepth = 10): \Illuminate\Database\Eloquent\Collection
    {
        // Protection against infinite recursion
        if (in_array($group->id, $visitedGroups) || count($visitedGroups) >= $maxDepth) {
            Log::warning("Circular reference detected or max depth reached for group {$group->id} ({$group->name})", [
                'visited_groups' => $visitedGroups,
                'current_depth' => count($visitedGroups),
                'max_depth' => $maxDepth,
            ]);

            return new \Illuminate\Database\Eloquent\Collection();
        }

        $descendantGroups = new \Illuminate\Database\Eloquent\Collection();
        $newVisitedGroups = array_merge($visitedGroups, [$group->id]);

        // Get groups that have this group as a member
        $groupsWithThisGroupAsMember = Group::whereHas('groupMembersFromMemberable', function ($query) use ($group) {
            $query->where('member_id', $group->id)
                  ->where('member_type', Group::class);
        })->get();

        foreach ($groupsWithThisGroupAsMember as $parentGroup) {
            $descendantGroups->push($parentGroup);

            // Recursively get descendants of this parent group, passing visited groups
            $deeperDescendants = $this->getDescendantGroups($parentGroup, $newVisitedGroups, $maxDepth);
            $descendantGroups = $descendantGroups->merge($deeperDescendants);
        }

        return $descendantGroups->unique('id');
    }

    /**
     * Invalidate cache for all users in a specific group
     */
    private function invalidateCacheForUsersInGroup(Group $group): void
    {
        // Get all users directly in this group
        $usersInGroup = User::whereHas('groupMembersFromMemberable', function ($query) use ($group) {
            $query->where('group_id', $group->id)
                  ->where('member_type', User::class);
        })->get();

        foreach ($usersInGroup as $user) {
            $this->permissionService->invalidateUserCache($user->id);
        }

        Log::info("Invalidated cache for {$usersInGroup->count()} users in group {$group->id} ({$group->name})");
    }
}
