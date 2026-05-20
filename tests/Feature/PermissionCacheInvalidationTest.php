<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\User;
use ProcessMaker\Services\PermissionServiceManager;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class PermissionCacheInvalidationTest extends TestCase
{
    use RefreshDatabase, RequestHelper;

    private PermissionServiceManager $permissionService;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the user is created by the trait
        if (!$this->user) {
            $this->user = User::factory()->create([
                'password' => Hash::make('password'),
                'is_administrator' => true,
            ]);
        }

        // Ensure the user has the edit-users permission for the API call
        $editUsersPermission = Permission::where('name', 'edit-users')->first();
        if ($editUsersPermission) {
            $this->user->permissions()->attach($editUsersPermission->id);
        }

        $this->permissionService = app(PermissionServiceManager::class);
    }

    public function test_permission_cache_is_invalidated_when_permissions_updated()
    {
        // Create test permissions
        $permission1 = Permission::factory()->create([
            'name' => 'test-permission',
            'title' => 'Test Permission',
        ]);

        $permission2 = Permission::factory()->create([
            'name' => 'another-permission',
            'title' => 'Another Permission',
        ]);

        // Give permission to user
        $this->user->permissions()->attach($permission1->id);

        // Warm up the cache
        $this->permissionService->warmUpUserCache($this->user->id);

        // Verify permission is cached
        $cachedPermissions = Cache::get("user_permissions:{$this->user->id}");
        $this->assertNotNull($cachedPermissions);
        $this->assertContains('test-permission', $cachedPermissions);

        // Update permissions via API
        $response = $this->apiCall('PUT', '1.0/permissions', [
            'user_id' => $this->user->id,
            'permission_names' => ['test-permission', 'another-permission'],
        ]);

        $this->assertEquals(204, $response->getStatusCode());

        // Verify cache is invalidated
        $cachedPermissionsAfterUpdate = Cache::get("user_permissions:{$this->user->id}");
        $this->assertNull($cachedPermissionsAfterUpdate);

        // Verify new permissions are loaded from database
        $freshPermissions = $this->permissionService->getUserPermissions($this->user->id);
        $this->assertContains('test-permission', $freshPermissions);
        $this->assertContains('another-permission', $freshPermissions);
    }

    public function test_permission_cache_is_invalidated_when_user_permissions_removed()
    {
        // Create test permissions
        $permission1 = Permission::factory()->create(['name' => 'permission-1']);
        $permission2 = Permission::factory()->create(['name' => 'permission-2']);

        // Give both permissions to user
        $this->user->permissions()->attach([$permission1->id, $permission2->id]);

        // Warm up the cache
        $this->permissionService->warmUpUserCache($this->user->id);

        // Verify permissions are cached
        $cachedPermissions = Cache::get("user_permissions:{$this->user->id}");
        $this->assertNotNull($cachedPermissions);
        $this->assertContains('permission-1', $cachedPermissions);
        $this->assertContains('permission-2', $cachedPermissions);

        // Remove one permission via API
        $response = $this->apiCall('PUT', '1.0/permissions', [
            'user_id' => $this->user->id,
            'permission_names' => ['permission-1'],
        ]);

        $this->assertEquals(204, $response->getStatusCode());

        // Verify cache is invalidated
        $cachedPermissionsAfterUpdate = Cache::get("user_permissions:{$this->user->id}");
        $this->assertNull($cachedPermissionsAfterUpdate);

        // Verify updated permissions are loaded from database
        $freshPermissions = $this->permissionService->getUserPermissions($this->user->id);
        $this->assertContains('permission-1', $freshPermissions);
        $this->assertNotContains('permission-2', $freshPermissions);
    }

    public function test_group_permission_update_does_not_logout_redis_backed_session()
    {
        $this->ensureRedisSessionAndCacheAreAvailable();

        $originalPermission = Permission::factory()->create(['name' => 'redis-group-permission']);
        Permission::factory()->create(['name' => 'redis-group-permission-updated']);
        $group = Group::factory()->create(['name' => 'Redis Permission Group']);
        $affectedUser = User::factory()->create([
            'password' => Hash::make('password'),
            'is_administrator' => false,
        ]);

        GroupMember::factory()->create([
            'group_id' => $group->id,
            'member_type' => User::class,
            'member_id' => $affectedUser->id,
        ]);

        $group->permissions()->sync([$originalPermission->id]);

        $this->permissionService->warmUpUserCache($affectedUser->id);

        $cachedPermissions = Cache::get("user_permissions:{$affectedUser->id}");
        $this->assertNotNull($cachedPermissions);
        $this->assertContains('redis-group-permission', $cachedPermissions);

        $this->actingAs($this->user, 'web')
            ->post(route('keep-alive'))
            ->assertNoContent();

        Passport::actingAs($this->user);

        $this->json('PUT', '/api/1.0/permissions', [
            'group_id' => $group->id,
            'permission_names' => ['redis-group-permission-updated'],
        ])->assertNoContent();

        $this->post(route('keep-alive'))->assertNoContent();
        $this->assertAuthenticatedAs($this->user, 'web');

        $freshPermissions = $this->permissionService->getUserPermissions($affectedUser->id);
        $this->assertContains('redis-group-permission-updated', $freshPermissions);
        $this->assertNotContains('redis-group-permission', $freshPermissions);
    }

    private function ensureRedisSessionAndCacheAreAvailable(): void
    {
        config()->set('cache.default', 'redis');
        config()->set('session.driver', 'redis');
        config()->set('session.connection', 'default');

        try {
            Redis::connection('default')->ping();
            Redis::connection('cache')->ping();
        } catch (\Throwable $e) {
            $this->markTestSkipped(
                'Redis is not available for the permission cache invalidation regression test: ' . $e->getMessage()
            );
        }
    }
}
