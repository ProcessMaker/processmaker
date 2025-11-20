<?php

namespace ProcessMaker\Contracts;

interface PermissionCacheInterface
{
    /**
     * Get cached permissions for a user
     */
    public function getUserPermissions(int $userId): ?array;

    /**
     * Cache user permissions
     */
    public function cacheUserPermissions(int $userId, array $permissions): void;

    /**
     * Get cached permissions for a group
     */
    public function getGroupPermissions(int $groupId): ?array;

    /**
     * Cache group permissions
     */
    public function cacheGroupPermissions(int $groupId, array $permissions): void;

    /**
     * Invalidate user permissions cache
     */
    public function invalidateUserPermissions(int $userId): void;

    /**
     * Invalidate group permissions cache
     */
    public function invalidateGroupPermissions(int $groupId): void;

    /**
     * Clear all permission caches
     */
    public function clearAll(): void;
}
