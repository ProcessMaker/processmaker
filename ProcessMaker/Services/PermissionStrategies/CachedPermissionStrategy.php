<?php

namespace ProcessMaker\Services\PermissionStrategies;

use ProcessMaker\Contracts\PermissionCacheInterface;
use ProcessMaker\Contracts\PermissionRepositoryInterface;
use ProcessMaker\Contracts\PermissionStrategyInterface;

class CachedPermissionStrategy implements PermissionStrategyInterface
{
    private PermissionCacheInterface $cacheService;

    private PermissionRepositoryInterface $repository;

    public function __construct(
        PermissionCacheInterface $cacheService,
        PermissionRepositoryInterface $repository
    ) {
        $this->cacheService = $cacheService;
        $this->repository = $repository;
    }

    /**
     * Check if user has permission using cached strategy
     */
    public function hasPermission(int $userId, string $permission): bool
    {
        // First, try to get from cache
        $cachedPermissions = $this->cacheService->getUserPermissions($userId);

        if ($cachedPermissions !== null) {
            return in_array($permission, $cachedPermissions);
        }

        // If not in cache, get from repository and cache
        $permissions = $this->repository->getUserPermissions($userId);
        $this->cacheService->cacheUserPermissions($userId, $permissions);

        return in_array($permission, $permissions);
    }

    /**
     * Get strategy name for identification
     */
    public function getStrategyName(): string
    {
        return 'cached';
    }

    /**
     * Check if this strategy can handle the permission check
     */
    public function canHandle(string $permission): bool
    {
        // This strategy can handle all permission types
        return true;
    }

    /**
     * Warm up cache for a user
     */
    public function warmUpCache(int $userId): void
    {
        $permissions = $this->repository->getUserPermissions($userId);
        $this->cacheService->cacheUserPermissions($userId, $permissions);
    }

    /**
     * Invalidate cache for a user
     */
    public function invalidateCache(int $userId): void
    {
        $this->cacheService->invalidateUserPermissions($userId);
    }
}
