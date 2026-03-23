<?php

namespace ProcessMaker\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Contracts\PermissionCacheInterface;

class PermissionCacheService implements PermissionCacheInterface
{
    private const USER_PERMISSIONS_TTL = 3600; // 1 hour

    private const GROUP_PERMISSIONS_TTL = 7200; // 2 hours

    private const USER_PERMISSIONS_KEY = 'user_permissions';

    private const GROUP_PERMISSIONS_KEY = 'group_permissions';

    private const LEGACY_USER_PERMISSIONS_KEY = 'user';

    private const TRACKED_PERMISSION_KEYS = 'permission_cache_keys';

    /**
     * Get cached permissions for a user
     */
    public function getUserPermissions(int $userId): ?array
    {
        $key = $this->getUserPermissionsKey($userId);

        try {
            return Cache::get($key);
        } catch (\Exception $e) {
            Log::warning("Failed to get cached user permissions for user {$userId}: " . $e->getMessage());

            return null;
        }
    }

    /**
     * Cache user permissions
     */
    public function cacheUserPermissions(int $userId, array $permissions): void
    {
        $key = $this->getUserPermissionsKey($userId);

        try {
            Cache::put($key, $permissions, self::USER_PERMISSIONS_TTL);
            $this->trackPermissionKey($key);
        } catch (\Exception $e) {
            Log::warning("Failed to cache user permissions for user {$userId}: " . $e->getMessage());
        }
    }

    /**
     * Get cached permissions for a group
     */
    public function getGroupPermissions(int $groupId): ?array
    {
        $key = $this->getGroupPermissionsKey($groupId);

        try {
            return Cache::get($key);
        } catch (\Exception $e) {
            Log::warning("Failed to get cached group permissions for group {$groupId}: " . $e->getMessage());

            return null;
        }
    }

    /**
     * Cache group permissions
     */
    public function cacheGroupPermissions(int $groupId, array $permissions): void
    {
        $key = $this->getGroupPermissionsKey($groupId);

        try {
            Cache::put($key, $permissions, self::GROUP_PERMISSIONS_TTL);
            $this->trackPermissionKey($key);
        } catch (\Exception $e) {
            Log::warning("Failed to cache group permissions for group {$groupId}: " . $e->getMessage());
        }
    }

    /**
     * Invalidate user permissions cache
     */
    public function invalidateUserPermissions(int $userId): void
    {
        $keys = [
            $this->getUserPermissionsKey($userId),
            $this->getLegacyUserPermissionsKey($userId),
        ];

        try {
            foreach ($keys as $key) {
                Cache::forget($key);
                $this->untrackPermissionKey($key);
            }
        } catch (\Exception $e) {
            Log::warning("Failed to invalidate user permissions cache for user {$userId}: " . $e->getMessage());
        }
    }

    /**
     * Invalidate group permissions cache
     */
    public function invalidateGroupPermissions(int $groupId): void
    {
        $key = $this->getGroupPermissionsKey($groupId);

        try {
            Cache::forget($key);
            $this->untrackPermissionKey($key);
        } catch (\Exception $e) {
            Log::warning("Failed to invalidate group permissions cache for group {$groupId}: " . $e->getMessage());
        }
    }

    /**
     * Clear all permission caches
     */
    public function clearAll(): void
    {
        try {
            foreach ($this->getTrackedPermissionKeys() as $key) {
                Cache::forget($key);
            }

            Cache::forget(self::TRACKED_PERMISSION_KEYS);
        } catch (\Exception $e) {
            Log::warning('Failed to clear all permission caches: ' . $e->getMessage());
        }
    }

    /**
     * Get cache key for user permissions
     */
    private function getUserPermissionsKey(int $userId): string
    {
        return self::USER_PERMISSIONS_KEY . ":{$userId}";
    }

    /**
     * Get cache key for group permissions
     */
    private function getGroupPermissionsKey(int $groupId): string
    {
        return self::GROUP_PERMISSIONS_KEY . ":{$groupId}";
    }

    /**
     * Get cache key for legacy user permissions.
     */
    private function getLegacyUserPermissionsKey(int $userId): string
    {
        return self::LEGACY_USER_PERMISSIONS_KEY . "_{$userId}_permissions";
    }

    /**
     * Warm up cache for a user
     */
    public function warmUpUserCache(int $userId, array $permissions): void
    {
        $this->cacheUserPermissions($userId, $permissions);
    }

    /**
     * Warm up cache for a group
     */
    public function warmUpGroupCache(int $groupId, array $permissions): void
    {
        $this->cacheGroupPermissions($groupId, $permissions);
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        return [
            'user_permissions_ttl' => self::USER_PERMISSIONS_TTL,
            'group_permissions_ttl' => self::GROUP_PERMISSIONS_TTL,
            'cache_driver' => config('cache.default'),
        ];
    }

    /**
     * Track service-managed permission keys so clearAll() can stay scoped.
     */
    private function trackPermissionKey(string $key): void
    {
        $keys = $this->getTrackedPermissionKeys();
        if (!in_array($key, $keys, true)) {
            $keys[] = $key;
            Cache::forever(self::TRACKED_PERMISSION_KEYS, $keys);
        }
    }

    /**
     * Stop tracking a permission key after explicit invalidation.
     */
    private function untrackPermissionKey(string $key): void
    {
        $keys = array_values(array_filter(
            $this->getTrackedPermissionKeys(),
            fn ($trackedKey) => $trackedKey !== $key
        ));

        if (empty($keys)) {
            Cache::forget(self::TRACKED_PERMISSION_KEYS);

            return;
        }

        Cache::forever(self::TRACKED_PERMISSION_KEYS, $keys);
    }

    /**
     * Read the tracked permission keys index.
     */
    private function getTrackedPermissionKeys(): array
    {
        $keys = Cache::get(self::TRACKED_PERMISSION_KEYS, []);

        return is_array($keys) ? $keys : [];
    }
}
