<?php

namespace Tests\Unit\ProcessMaker\Traits;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\User;
use ProcessMaker\Services\PermissionServiceManager;
use Tests\TestCase;

class HasAuthorizationOptimizedTest extends TestCase
{
    use RefreshDatabase;

    private array $permissions = [];

    private PermissionServiceManager $permissionService;

    private float $testStartTime;

    protected function setUp(): void
    {
        parent::setUp();

        // Store start time for execution tracking
        $this->testStartTime = microtime(true);

        echo "\n🧪 TEST SUITE\n\n";

        // Create test permissions
        $this->createTestPermissions();

        // Get the permission service
        $this->permissionService = app(PermissionServiceManager::class);

        Cache::flush();
    }

    /**
     * Test performance improvement with optimized permission checking
     *
     * This test measures the performance and scalability of the optimized
     * permission system by testing with different numbers of user groups.
     */
    public function testOptimizedPermissionPerformance()
    {
        echo "\n🚀 PERFORMANCE\n";

        $user25 = $this->createUserWithGroups(25);
        DB::flushQueryLog();

        DB::enableQueryLog();
        $startTime = microtime(true);
        $permissions = $this->permissionService->getUserPermissions($user25->id);
        $duration25 = (microtime(true) - $startTime) * 1000;
        $queries25 = count(DB::getQueryLog());

        echo "25g: {$duration25}ms | {$queries25}q | " . count($permissions) . "p\n";

        $user50 = $this->createUserWithGroups(50);
        DB::flushQueryLog();

        DB::enableQueryLog();
        $startTime = microtime(true);
        $permissions = $this->permissionService->getUserPermissions($user50->id);
        $duration50 = (microtime(true) - $startTime) * 1000;
        $queries50 = count(DB::getQueryLog());

        echo "50g: {$duration50}ms | {$queries50}q | " . count($permissions) . "p\n";
        echo '📊 ' . round($queries50 / $queries25, 1) . 'x | ' . round($duration50, 2) . "ms\n\n";

        // Assertions
        $this->assertNotEmpty($permissions, 'Should load some permissions');
        $this->assertLessThan(100, $duration50, 'Should be fast (<100ms)');
        $this->assertLessThan(50, $queries50, 'Permission loading should use few queries (<50)');

        echo "✅ PASSED\n\n";
    }

    /**
     * Test cache effectiveness
     *
     * This test validates that the permission caching system is working
     * correctly and provides significant performance improvements.
     */
    public function testCacheEffectiveness()
    {
        echo "\n🎯 CACHE\n";

        $user = $this->createUserWithGroups(25);

        DB::enableQueryLog();
        $startTime = microtime(true);
        $permissions = $this->permissionService->getUserPermissions($user->id);
        $firstDuration = (microtime(true) - $startTime) * 1000;
        $firstQueries = count(DB::getQueryLog());

        echo "DB: {$firstDuration}ms | {$firstQueries}q | " . count($permissions) . "p\n";

        DB::enableQueryLog();
        $startTime = microtime(true);
        $permissions = $this->permissionService->getUserPermissions($user->id);
        $secondDuration = (microtime(true) - $startTime) * 1000;
        $secondQueries = count(DB::getQueryLog());

        echo "Cache: {$secondDuration}ms | {$secondQueries}q | " . count($permissions) . "p\n";

        $speedImprovement = $firstDuration / $secondDuration;
        $queryReduction = $firstQueries / $secondQueries;
        $cacheEfficiency = round((1 - ($secondDuration / $firstDuration)) * 100, 1);

        echo '📊 ' . number_format($speedImprovement, 1) . "x | {$cacheEfficiency}%\n\n";

        // Assertions
        $this->assertGreaterThan(5, $speedImprovement, 'Cache should provide significant speed improvement (>5x)');
        $this->assertGreaterThan(0.9, $queryReduction, 'Should use similar number of queries (cache hit)');

        echo "✅ PASSED\n\n";
    }

    /**
     * Create test permissions
     */
    private function createTestPermissions(): void
    {
        $permissionNames = [
            'view-processes', 'create-processes', 'edit-processes', 'delete-processes',
            'view-scripts', 'create-scripts', 'edit-scripts', 'delete-scripts',
            'view-screens', 'create-screens', 'edit-screens', 'delete-screens',
            'view-users', 'create-users', 'edit-users', 'delete-users',
            'view-groups', 'create-groups', 'edit-groups', 'delete-groups',
        ];

        echo "\n🔧 SETUP\n";

        foreach ($permissionNames as $name) {
            try {
                // Try to find existing permission first
                $permission = Permission::where('name', $name)->first();

                if (!$permission) {
                    $permission = Permission::factory()->create([
                        'name' => $name,
                        'title' => ucwords(str_replace('-', ' ', $name)),
                    ]);
                }

                $this->permissions[$name] = $permission;
            } catch (\Exception $e) {
                echo "   ⚠️  Warning: Error with permission '{$name}': " . $e->getMessage() . "\n";
                // Create a dummy permission to avoid array key errors
                $this->permissions[$name] = (object) ['id' => 999, 'name' => $name];
            }
        }

        echo '📊 ' . count($this->permissions) . " permissions\n";
    }

    /**
     * Create user with specified number of groups
     */
    private function createUserWithGroups(int $numGroups): User
    {
        $user = User::factory()->create();

        $directPermissionKeys = array_rand($this->permissions, 2);
        foreach ($directPermissionKeys as $key) {
            $permission = $this->permissions[$key];
            if ($permission && isset($permission->id)) {
                $user->permissions()->attach($permission->id);
            }
        }

        for ($i = 0; $i < $numGroups; $i++) {
            $group = Group::factory()->create();

            $group->groupMembers()->create([
                'member_id' => $user->id,
                'member_type' => User::class,
            ]);

            $groupPermissionKeys = array_rand($this->permissions, 3);
            foreach ($groupPermissionKeys as $key) {
                $permission = $this->permissions[$key];
                if ($permission && isset($permission->id)) {
                    $group->permissions()->attach($permission->id);
                }
            }
        }

        return $user;
    }

    /**
     * Get permission categories for better organization
     */
    private function getPermissionCategories(): string
    {
        $categories = [];
        foreach (array_keys($this->permissions) as $permission) {
            $parts = explode('-', $permission);
            if (count($parts) >= 2) {
                $categories[] = $parts[1]; // e.g., "processes", "scripts", "screens"
            }
        }

        return implode(', ', array_unique($categories));
    }

    /**
     * Get permission distribution statistics
     */
    private function getPermissionDistribution(): string
    {
        $distribution = [];
        foreach (array_keys($this->permissions) as $permission) {
            $parts = explode('-', $permission);
            if (count($parts) >= 2) {
                $category = $parts[1];
                $distribution[$category] = ($distribution[$category] ?? 0) + 1;
            }
        }

        $result = [];
        foreach ($distribution as $category => $count) {
            $result[] = "{$category}: {$count}";
        }

        return implode(', ', $result);
    }

    /**
     * Get category for a specific permission
     */
    private function getPermissionCategory(string $permissionName): string
    {
        $parts = explode('-', $permissionName);
        if (count($parts) >= 2) {
            return ucfirst($parts[1]); // e.g., "Processes", "Scripts", "Screens"
        }

        return 'Other';
    }

    /**
     * Format permission distribution for display
     */
    private function formatPermissionDistribution(array $distribution): string
    {
        $result = [];
        foreach ($distribution as $category => $count) {
            $result[] = "{$category}: {$count}";
        }

        return implode(', ', $result);
    }

    /**
     * Test suite completion summary
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        // Only show summary if tests are running
        if (app()->runningInConsole()) {
            echo "\n✅ " . round(microtime(true) - $this->testStartTime, 2) . "s\n";
        }
    }
}
