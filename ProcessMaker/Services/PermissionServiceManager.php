<?php

namespace ProcessMaker\Services;

use Illuminate\Support\Facades\Log;
use ProcessMaker\Contracts\PermissionCacheInterface;
use ProcessMaker\Contracts\PermissionRepositoryInterface;
use ProcessMaker\Contracts\PermissionStrategyInterface;
use ProcessMaker\Services\PermissionStrategies\CachedPermissionStrategy;

class PermissionServiceManager
{
    private PermissionRepositoryInterface $repository;

    private PermissionCacheInterface $cacheService;

    private ?PermissionStrategyInterface $defaultStrategy = null;

    public function __construct(
        PermissionRepositoryInterface $repository,
        PermissionCacheInterface $cacheService
    ) {
        $this->repository = $repository;
        $this->cacheService = $cacheService;

        // Register default strategy
        $this->defaultStrategy = new CachedPermissionStrategy($cacheService, $repository);
    }

    /**
     * Get all permissions for a user
     */
    public function getUserPermissions(int $userId): array
    {
        // Try cache first
        $cachedPermissions = $this->cacheService->getUserPermissions($userId);

        if ($cachedPermissions !== null) {
            return $cachedPermissions;
        }

        // Get from repository and cache
        $permissions = $this->repository->getUserPermissions($userId);
        $this->cacheService->cacheUserPermissions($userId, $permissions);

        return $permissions;
    }

    /**
     * Check if user has permission
     */
    public function userHasPermission(int $userId, string $permission): bool
    {
        return $this->defaultStrategy->hasPermission($userId, $permission);
    }

    /**
     * Check if user has any of the given permissions
     */
    public function userHasAnyPermission(int $userId, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->userHasPermission($userId, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the given permissions
     */
    public function userHasAllPermissions(int $userId, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->userHasPermission($userId, $permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Warm up cache for a user
     */
    public function warmUpUserCache(int $userId): void
    {
        try {
            $permissions = $this->repository->getUserPermissions($userId);
            $this->cacheService->cacheUserPermissions($userId, $permissions);

            Log::info("Warmed up permission cache for user {$userId}");
        } catch (\Exception $e) {
            Log::error("Failed to warm up permission cache for user {$userId}: " . $e->getMessage());
        }
    }

    /**
     * Invalidate cache for a user
     */
    public function invalidateUserCache(int $userId): void
    {
        $this->cacheService->invalidateUserPermissions($userId);
        Log::info("Invalidated permission cache for user {$userId}");
    }

    /**
     * Invalidate all permission caches
     */
    public function invalidateAll(): void
    {
        $this->cacheService->clearAll();
    }
}
