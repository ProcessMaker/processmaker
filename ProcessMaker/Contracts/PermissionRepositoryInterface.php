<?php

namespace ProcessMaker\Contracts;

interface PermissionRepositoryInterface
{
    /**
     * Get all permissions for a user (direct + group permissions)
     */
    public function getUserPermissions(int $userId): array;

    /**
     * Get direct user permissions
     */
    public function getDirectUserPermissions(int $userId): array;

    /**
     * Get group permissions for a user
     */
    public function getGroupPermissions(int $userId): array;

    /**
     * Check if user has a specific permission
     */
    public function userHasPermission(int $userId, string $permission): bool;

    /**
     * Get permissions for a specific group
     */
    public function getGroupPermissionsById(int $groupId): array;

    /**
     * Get nested group permissions (recursive)
     */
    public function getNestedGroupPermissions(int $groupId): array;
}
