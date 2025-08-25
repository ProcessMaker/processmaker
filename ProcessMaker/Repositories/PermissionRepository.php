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
     * Get all permissions for a user (direct + group permissions)
     */
    public function getUserPermissions(int $userId): array
    {
        // Use Eloquent relationships instead of raw SQL
        $user = User::find($userId);
        if (!$user) {
            return [];
        }

        // Get direct user permissions
        $userPermissions = $user->permissions()->pluck('name')->toArray();

        // Get group permissions
        $groupPermissions = $this->getGroupPermissions($userId);

        // Merge and deduplicate
        $allPermissions = array_merge($userPermissions, $groupPermissions);
        $allPermissions = array_unique($allPermissions);

        return $this->addCategoryViewPermissions($allPermissions);
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
     * Get group permissions for a user (optimized)
     */
    public function getGroupPermissions(int $userId): array
    {
        // Use Eloquent relationships with eager loading to avoid N+1 queries
        $user = User::with([
            'groupMembersFromMemberable.group.permissions',
        ])->find($userId);

        if (!$user) {
            return [];
        }

        $permissions = [];

        // Get permissions from all groups the user belongs to (already loaded)
        foreach ($user->groupMembersFromMemberable as $groupMember) {
            $group = $groupMember->group;
            if ($group && $group->permissions) {
                $groupPermissions = $group->permissions->pluck('name')->toArray();
                $permissions = array_merge($permissions, $groupPermissions);
            }
        }

        return array_unique($permissions);
    }

    /**
     * Check if user has a specific permission (optimized)
     */
    public function userHasPermission(int $userId, string $permission): bool
    {
        // Use Eloquent relationships with eager loading to avoid N+1 queries
        $user = User::with([
            'permissions',
            'groupMembersFromMemberable.group.permissions',
        ])->find($userId);

        if (!$user) {
            return false;
        }

        // Check direct user permissions (already loaded)
        $hasDirectPermission = $user->permissions->contains('name', $permission);
        if ($hasDirectPermission) {
            return true;
        }

        // Check group permissions (already loaded)
        foreach ($user->groupMembersFromMemberable as $groupMember) {
            $group = $groupMember->group;
            if ($group && $group->permissions) {
                $hasGroupPermission = $group->permissions->contains('name', $permission);
                if ($hasGroupPermission) {
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
}
