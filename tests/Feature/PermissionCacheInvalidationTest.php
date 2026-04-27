<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
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
}
