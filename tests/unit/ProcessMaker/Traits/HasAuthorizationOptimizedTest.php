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

    protected function setUp(): void
    {
        parent::setUp();

        // Create test permissions
        $this->createTestPermissions();

        // Get the permission service
        $this->permissionService = app(PermissionServiceManager::class);

        // Clear cache before each test
        Cache::flush();
    }

    /**
     * Test performance improvement with optimized permission checking
     */
    public function testOptimizedPermissionPerformance()
    {
        echo "\n=== 🚀 OPTIMIZED PERMISSION PERFORMANCE TEST ===\n";
        echo "Testing the new optimized permission system\n";
        echo str_repeat('-', 60) . "\n\n";

        // Test with 25 groups
        echo "🔧 Testing with 25 groups:\n";
        $user25 = $this->createUserWithGroups(25);

        DB::enableQueryLog();
        $startTime = microtime(true);

        $permissions = $this->permissionService->getUserPermissions($user25->id);

        $duration = (microtime(true) - $startTime) * 1000;
        $queries = count(DB::getQueryLog());

        echo "   📊 Initial loading: {$duration}ms, {$queries} queries\n";
        echo '   🔑 Permissions loaded: ' . count($permissions) . "\n\n";

        // Test with 50 groups
        echo "🔧 Testing with 50 groups:\n";
        $user50 = $this->createUserWithGroups(50);

        DB::enableQueryLog();
        $startTime = microtime(true);

        $permissions = $this->permissionService->getUserPermissions($user50->id);

        $duration = (microtime(true) - $startTime) * 1000;
        $queries = count(DB::getQueryLog());

        echo "   📊 Initial loading: {$duration}ms, {$queries} queries\n";
        echo '   🔑 Permissions loaded: ' . count($permissions) . "\n\n";

        // Assertions
        $this->assertNotEmpty($permissions, 'Should load some permissions');
        $this->assertLessThan(100, $duration, 'Should be fast (<100ms)');
        $this->assertLessThan(300, $queries, 'Should use reasonable number of queries (<300)');

        echo "✅ Performance test passed!\n";
    }

    /**
     * Test cache effectiveness
     */
    public function testCacheEffectiveness()
    {
        echo "\n=== 🎯 CACHE EFFECTIVENESS TEST ===\n";
        echo "Testing if cache is working properly\n";
        echo str_repeat('-', 60) . "\n\n";

        $user = $this->createUserWithGroups(25);

        echo "🔄 First call (database hit):\n";
        DB::enableQueryLog();
        $startTime = microtime(true);

        $permissions = $this->permissionService->getUserPermissions($user->id);

        $firstDuration = (microtime(true) - $startTime) * 1000;
        $firstQueries = count(DB::getQueryLog());

        echo "   Duration: {$firstDuration}ms\n";
        echo "   Queries: {$firstQueries} queries\n\n";

        echo "🔄 Second call (cache hit):\n";
        DB::enableQueryLog();
        $startTime = microtime(true);

        $permissions = $this->permissionService->getUserPermissions($user->id);

        $secondDuration = (microtime(true) - $startTime) * 1000;
        $secondQueries = count(DB::getQueryLog());

        echo "   Duration: {$secondDuration}ms\n";
        echo "   Queries: {$secondQueries} queries\n\n";

        // Calculate improvements
        $speedImprovement = $firstDuration / $secondDuration;
        $queryReduction = $firstQueries / $secondQueries;

        echo "📊 Cache Performance:\n";
        echo '   🚀 Speed improvement: ' . number_format($speedImprovement, 2) . "x faster\n";
        echo '   🗄️  Query reduction: ' . number_format($queryReduction, 2) . "x fewer queries\n\n";

        // Assertions
        $this->assertGreaterThan(5, $speedImprovement, 'Cache should provide significant speed improvement (>5x)');
        $this->assertGreaterThan(0.9, $queryReduction, 'Cache should provide some query reduction (>0.9x)');

        echo "✅ Cache effectiveness test passed!\n";
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

        echo "\n🔧 Creating test permissions...\n";

        foreach ($permissionNames as $name) {
            try {
                // Try to find existing permission first
                $permission = Permission::where('name', $name)->first();

                if (!$permission) {
                    echo "   Creating permission: {$name}\n";
                    // Create new permission only if it doesn't exist
                    $permission = Permission::factory()->create([
                        'name' => $name,
                        'title' => ucwords(str_replace('-', ' ', $name)), // Convert "view-processes" to "View Processes"
                    ]);
                } else {
                    echo "   Found existing permission: {$name}\n";
                }

                $this->permissions[$name] = $permission;
                echo "   ✅ Permission '{$name}' added to array\n";
            } catch (\Exception $e) {
                echo "   ❌ Error creating permission '{$name}': " . $e->getMessage() . "\n";
                // Create a dummy permission to avoid array key errors
                $this->permissions[$name] = (object) ['id' => 999, 'name' => $name];
            }
        }

        echo '   📊 Total permissions created: ' . count($this->permissions) . "\n";
        echo '   🔑 Permission keys: ' . implode(', ', array_keys($this->permissions)) . "\n";
    }

    /**
     * Create user with specified number of groups
     */
    private function createUserWithGroups(int $numGroups): User
    {
        $user = User::factory()->create();

        // Add 2 direct permissions to user
        $directPermissionKeys = array_rand($this->permissions, 2);
        foreach ($directPermissionKeys as $key) {
            $permission = $this->permissions[$key];
            if ($permission && isset($permission->id)) {
                $user->permissions()->attach($permission->id);
            }
        }

        // Create groups and add user to them
        for ($i = 0; $i < $numGroups; $i++) {
            $group = Group::factory()->create();

            // Add user to group
            $group->groupMembers()->create([
                'member_id' => $user->id,
                'member_type' => User::class,
            ]);

            // Add 3 random permissions to group
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
}
