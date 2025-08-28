<?php

namespace ProcessMaker\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Contracts\PermissionRepositoryInterface;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\User;

class PermissionRepository implements PermissionRepositoryInterface
{
    /**
     * Get all permissions for a user (optimized with hierarchical inheritance)
     */
    public function getUserPermissions(int $userId): array
    {
        $user = User::with([
            'permissions',
            'groupMembersFromMemberable.group.permissions',
        ])->find($userId);

        if (!$user) {
            return [];
        }

        $permissions = [];

        // Add direct user permissions
        if ($user->permissions) {
            foreach ($user->permissions as $permission) {
                $permissions[] = $permission->name;
            }
        }

        // Add group permissions (including nested groups through recursion)
        foreach ($user->groupMembersFromMemberable as $groupMember) {
            $group = $groupMember->group;
            if ($group && $group->permissions) {
                foreach ($group->permissions as $permission) {
                    $permissions[] = $permission->name;
                }

                // Get nested group permissions recursively
                $nestedPermissions = $this->getNestedGroupPermissionsOptimized($group);
                $permissions = array_merge($permissions, $nestedPermissions);
            }
        }

        return array_unique($permissions);
    }

    /**
     * Get direct user permissions
     */
    public function getDirectUserPermissions(int $userId): array
    {
        $user = User::find($userId);
        if (!$user) {
            return [];
        }

        return $user->permissions()->pluck('name')->toArray();
    }

    /**
     * Get group permissions for a user (optimized with hierarchical inheritance)
     */
    public function getGroupPermissions(int $groupId): array
    {
        $group = Group::with([
            'permissions',
            'groupMembersFromMemberable.group.permissions',
        ])->find($groupId);

        if (!$group) {
            return [];
        }

        $permissions = [];

        // Add direct group permissions
        if ($group->permissions) {
            foreach ($group->permissions as $permission) {
                $permissions[] = $permission->name;
            }
        }

        // Add nested group permissions recursively
        $nestedPermissions = $this->getNestedGroupPermissionsOptimized($group);
        $permissions = array_merge($permissions, $nestedPermissions);

        return array_unique($permissions);
    }

    /**
     * Check if user has a specific permission (optimized with hierarchical inheritance)
     */
    public function userHasPermission(int $userId, string $permission): bool
    {
        $user = User::with([
            'permissions',
            'groupMembersFromMemberable.group.permissions',
        ])->find($userId);

        if (!$user) {
            return false;
        }

        // Check direct user permissions
        if ($user->permissions) {
            foreach ($user->permissions as $userPermission) {
                if ($userPermission->name === $permission) {
                    return true;
                }
            }
        }

        // Check group permissions (including nested groups through recursion)
        foreach ($user->groupMembersFromMemberable as $groupMember) {
            $group = $groupMember->group;
            if ($group) {
                // Check direct group permissions
                if ($group->permissions) {
                    foreach ($group->permissions as $groupPermission) {
                        if ($groupPermission->name === $permission) {
                            return true;
                        }
                    }
                }

                // Check nested group permissions recursively
                if ($this->hasNestedGroupPermission($group, $permission)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get permissions for a specific group
     */
    public function getGroupPermissionsById(int $groupId): array
    {
        $group = Group::find($groupId);
        if (!$group) {
            return [];
        }

        return $group->permissions()->pluck('name')->toArray();
    }

    /**
     * Get nested group permissions (optimized recursive)
     */
    public function getNestedGroupPermissions(int $groupId): array
    {
        $group = Group::find($groupId);
        if (!$group) {
            return [];
        }

        $permissions = [];

        // Get direct group permissions
        $groupPermissions = $group->permissions()->pluck('name')->toArray();
        $permissions = array_merge($permissions, $groupPermissions);

        // Get nested group permissions recursively
        foreach ($group->groupMembersFromMemberable as $member) {
            if ($member->member_type === Group::class) {
                $nestedPermissions = $this->getNestedGroupPermissions($member->member_id);
                $permissions = array_merge($permissions, $nestedPermissions);
            }
        }

        return array_unique($permissions);
    }

    /**
     * Add category view permissions based on create/edit permissions
     */
    private function addCategoryViewPermissions(array $permissions): array
    {
        $addFor = [
            'processes' => 'view-process-categories',
            'scripts' => 'view-script-categories',
            'screens' => 'view-screen-categories',
        ];

        foreach ($addFor as $resource => $categoryPermission) {
            if (
                in_array('create-' . $resource, $permissions) ||
                in_array('edit-' . $resource, $permissions)
            ) {
                if (!in_array($categoryPermission, $permissions)) {
                    $permissions[] = $categoryPermission;
                }
            }
        }

        return $permissions;
    }

    /**
     * Get nested group permissions recursively with protection against infinite loops
     */
    private function getNestedGroupPermissionsOptimized(Group $group, array $visitedGroups = [], int $maxDepth = 10): array
    {
        // Protection against infinite recursion
        if (in_array($group->id, $visitedGroups) || count($visitedGroups) >= $maxDepth) {
            return [];
        }

        $permissions = [];
        $newVisitedGroups = array_merge($visitedGroups, [$group->id]);

        // Get groups that have this group as a member
        // Load the relationship if not already loaded
        if (!$group->relationLoaded('groupMembersFromMemberable')) {
            $group->load(['groupMembersFromMemberable.group.permissions']);
        }

        foreach ($group->groupMembersFromMemberable as $member) {
            if ($member->member_type === Group::class && $member->group) {
                $nestedGroup = $member->group;

                // Add direct permissions from nested group
                if ($nestedGroup->permissions) {
                    foreach ($nestedGroup->permissions as $permission) {
                        $permissions[] = $permission->name;
                    }
                }

                // Recursively get permissions from deeper nested groups
                $deeperPermissions = $this->getNestedGroupPermissionsOptimized($nestedGroup, $newVisitedGroups, $maxDepth);
                $permissions = array_merge($permissions, $deeperPermissions);
            }
        }

        return $permissions;
    }

    /**
     * Check if a group has a specific permission through nested inheritance
     */
    private function hasNestedGroupPermission(Group $group, string $permission, array $visitedGroups = [], int $maxDepth = 10): bool
    {
        // Protection against infinite recursion
        if (in_array($group->id, $visitedGroups) || count($visitedGroups) >= $maxDepth) {
            return false;
        }

        $newVisitedGroups = array_merge($visitedGroups, [$group->id]);

        // Load the relationship if not already loaded
        if (!$group->relationLoaded('groupMembersFromMemberable')) {
            $group->load(['groupMembersFromMemberable.group.permissions']);
        }

        foreach ($group->groupMembersFromMemberable as $member) {
            if ($member->member_type === Group::class && $member->group) {
                $nestedGroup = $member->group;

                // Check direct permissions from nested group
                if ($nestedGroup->permissions) {
                    foreach ($nestedGroup->permissions as $groupPermission) {
                        if ($groupPermission->name === $permission) {
                            return true;
                        }
                    }
                }

                // Recursively check permissions from deeper nested groups
                if ($this->hasNestedGroupPermission($nestedGroup, $permission, $newVisitedGroups, $maxDepth)) {
                    return true;
                }
            }
        }

        return false;
    }
}
