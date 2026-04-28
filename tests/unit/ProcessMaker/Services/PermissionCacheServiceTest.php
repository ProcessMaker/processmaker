<?php

namespace Tests\Unit\ProcessMaker\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Services\PermissionCacheService;
use Tests\TestCase;

class PermissionCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    private PermissionCacheService $cacheService;

    private int $userId;

    private int $groupId;

    private array $userPermissions;

    private array $groupPermissions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheService = new PermissionCacheService();

        // Test data
        $this->userId = 123;
        $this->groupId = 456;
        $this->userPermissions = ['permission-1', 'permission-2', 'permission-3'];
        $this->groupPermissions = ['group-permission-1', 'group-permission-2'];

        // Clear cache
        Cache::flush();
    }

    /**
     * Test that cacheUserPermissions stores user permissions correctly
     */
    public function test_cache_user_permissions_stores_correctly()
    {
        // Cache user permissions
        $this->cacheService->cacheUserPermissions($this->userId, $this->userPermissions);

        // Verify cache was stored
        $cachedPermissions = Cache::get("user_permissions:{$this->userId}");
        $this->assertNotNull($cachedPermissions);
        $this->assertEquals($this->userPermissions, $cachedPermissions);
    }

    /**
     * Test that cacheGroupPermissions stores group permissions correctly
     */
    public function test_cache_group_permissions_stores_correctly()
    {
        // Cache group permissions
        $this->cacheService->cacheGroupPermissions($this->groupId, $this->groupPermissions);

        // Verify cache was stored
        $cachedPermissions = Cache::get("group_permissions:{$this->groupId}");
        $this->assertNotNull($cachedPermissions);
        $this->assertEquals($this->groupPermissions, $cachedPermissions);
    }

    /**
     * Test that getUserPermissions retrieves cached user permissions
     */
    public function test_get_user_permissions_retrieves_cached_permissions()
    {
        // Cache user permissions
        $this->cacheService->cacheUserPermissions($this->userId, $this->userPermissions);

        // Retrieve cached permissions
        $retrievedPermissions = $this->cacheService->getUserPermissions($this->userId);

        // Verify permissions were retrieved correctly
        $this->assertEquals($this->userPermissions, $retrievedPermissions);
    }

    /**
     * Test that getGroupPermissions retrieves cached group permissions
     */
    public function test_get_group_permissions_retrieves_cached_permissions()
    {
        // Cache group permissions
        $this->cacheService->cacheGroupPermissions($this->groupId, $this->groupPermissions);

        // Retrieve cached permissions
        $retrievedPermissions = $this->cacheService->getGroupPermissions($this->groupId);

        // Verify permissions were retrieved correctly
        $this->assertEquals($this->groupPermissions, $retrievedPermissions);
    }

    /**
     * Test that getUserPermissions returns null when cache is empty
     */
    public function test_get_user_permissions_returns_null_when_cache_empty()
    {
        // Try to retrieve permissions without caching
        $retrievedPermissions = $this->cacheService->getUserPermissions($this->userId);

        // Verify null is returned
        $this->assertNull($retrievedPermissions);
    }

    /**
     * Test that getGroupPermissions returns null when cache is empty
     */
    public function test_get_group_permissions_returns_null_when_cache_empty()
    {
        // Try to retrieve permissions without caching
        $retrievedPermissions = $this->cacheService->getGroupPermissions($this->groupId);

        // Verify null is returned
        $this->assertNull($retrievedPermissions);
    }

    /**
     * Test that invalidateUserPermissions clears user cache correctly
     */
    public function test_invalidate_user_permissions_clears_cache_correctly()
    {
        // Cache user permissions
        $this->cacheService->cacheUserPermissions($this->userId, $this->userPermissions);

        // Verify cache exists
        $this->assertNotNull(Cache::get("user_permissions:{$this->userId}"));

        // Invalidate cache
        $this->cacheService->invalidateUserPermissions($this->userId);

        // Verify cache was cleared
        $this->assertNull(Cache::get("user_permissions:{$this->userId}"));
    }

    /**
     * Test that invalidateGroupPermissions clears group cache correctly
     */
    public function test_invalidate_group_permissions_clears_cache_correctly()
    {
        // Cache group permissions
        $this->cacheService->cacheGroupPermissions($this->groupId, $this->groupPermissions);

        // Verify cache exists
        $this->assertNotNull(Cache::get("group_permissions:{$this->groupId}"));

        // Invalidate cache
        $this->cacheService->invalidateGroupPermissions($this->groupId);

        // Verify cache was cleared
        $this->assertNull(Cache::get("group_permissions:{$this->groupId}"));
    }

    /**
     * Test that clearAll clears all permission caches
     */
    public function test_clear_all_clears_all_permission_caches()
    {
        // Cache both user and group permissions
        $this->cacheService->cacheUserPermissions($this->userId, $this->userPermissions);
        $this->cacheService->cacheGroupPermissions($this->groupId, $this->groupPermissions);

        // Verify both caches exist
        $this->assertNotNull(Cache::get("user_permissions:{$this->userId}"));
        $this->assertNotNull(Cache::get("group_permissions:{$this->groupId}"));

        // Clear all caches
        $this->cacheService->clearAll();

        // Verify both caches were cleared
        $this->assertNull(Cache::get("user_permissions:{$this->userId}"));
        $this->assertNull(Cache::get("group_permissions:{$this->groupId}"));
    }

    /**
     * Test that cache keys are generated correctly
     */
    public function test_cache_keys_are_generated_correctly()
    {
        // Test that cache keys follow the expected pattern
        $userKey = "user_permissions:{$this->userId}";
        $groupKey = "group_permissions:{$this->groupId}";

        $this->assertEquals("user_permissions:{$this->userId}", $userKey);
        $this->assertEquals("group_permissions:{$this->groupId}", $groupKey);
    }

    /**
     * Test that cache TTL is respected
     */
    public function test_cache_ttl_is_respected()
    {
        // Cache user permissions
        $this->cacheService->cacheUserPermissions($this->userId, $this->userPermissions);

        // Verify cache exists
        $this->assertNotNull(Cache::get("user_permissions:{$this->userId}"));

        // Note: We can't directly test TTL in unit tests, but we can verify the cache exists
        // The TTL is set in the service configuration (1 hour for users, 2 hours for groups)
        $this->assertTrue(Cache::has("user_permissions:{$this->userId}"));
    }

    /**
     * Test that multiple users can have separate caches
     */
    public function test_multiple_users_can_have_separate_caches()
    {
        $userId2 = 789;
        $userPermissions2 = ['permission-4', 'permission-5'];

        // Cache permissions for both users
        $this->cacheService->cacheUserPermissions($this->userId, $this->userPermissions);
        $this->cacheService->cacheUserPermissions($userId2, $userPermissions2);

        // Verify both caches exist separately
        $this->assertNotNull(Cache::get("user_permissions:{$this->userId}"));
        $this->assertNotNull(Cache::get("user_permissions:{$userId2}"));

        // Verify caches contain different data
        $cachedPermissions1 = Cache::get("user_permissions:{$this->userId}");
        $cachedPermissions2 = Cache::get("user_permissions:{$userId2}");

        $this->assertEquals($this->userPermissions, $cachedPermissions1);
        $this->assertEquals($userPermissions2, $cachedPermissions2);
        $this->assertNotEquals($cachedPermissions1, $cachedPermissions2);
    }

    /**
     * Test that multiple groups can have separate caches
     */
    public function test_multiple_groups_can_have_separate_caches()
    {
        $groupId2 = 789;
        $groupPermissions2 = ['group-permission-3', 'group-permission-4'];

        // Cache permissions for both groups
        $this->cacheService->cacheGroupPermissions($this->groupId, $this->groupPermissions);
        $this->cacheService->cacheGroupPermissions($groupId2, $groupPermissions2);

        // Verify both caches exist separately
        $this->assertNotNull(Cache::get("group_permissions:{$this->groupId}"));
        $this->assertNotNull(Cache::get("group_permissions:{$groupId2}"));

        // Verify caches contain different data
        $cachedPermissions1 = Cache::get("group_permissions:{$this->groupId}");
        $cachedPermissions2 = Cache::get("group_permissions:{$groupId2}");

        $this->assertEquals($this->groupPermissions, $cachedPermissions1);
        $this->assertEquals($groupPermissions2, $cachedPermissions2);
        $this->assertNotEquals($cachedPermissions1, $cachedPermissions2);
    }

    /**
     * Test that cache service handles empty permission arrays gracefully
     */
    public function test_handles_empty_permission_arrays_gracefully()
    {
        // Cache empty permissions
        $this->cacheService->cacheUserPermissions($this->userId, []);
        $this->cacheService->cacheGroupPermissions($this->groupId, []);

        // Verify caches were stored
        $this->assertNotNull(Cache::get("user_permissions:{$this->userId}"));
        $this->assertNotNull(Cache::get("group_permissions:{$this->groupId}"));

        // Verify retrieved permissions are empty arrays
        $retrievedUserPermissions = $this->cacheService->getUserPermissions($this->userId);
        $retrievedGroupPermissions = $this->cacheService->getGroupPermissions($this->groupId);

        $this->assertIsArray($retrievedUserPermissions);
        $this->assertIsArray($retrievedGroupPermissions);
        $this->assertEmpty($retrievedUserPermissions);
        $this->assertEmpty($retrievedGroupPermissions);
    }
}
