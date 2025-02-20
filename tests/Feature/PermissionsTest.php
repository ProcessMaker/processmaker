<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\AuthServiceProvider;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use RequestHelper;

    protected function withUserSetup()
    {
        // Our user should not be an admin.
        $this->user->is_administrator = false;
        $this->user->save();

        // Seed the permissions table.
        Artisan::call('db:seed', ['--class' => 'PermissionSeeder']);

        // Reboot our AuthServiceProvider. This is necessary so that it can
        // pick up the new permissions and setup gates for each of them.
        $asp = new AuthServiceProvider(app());
        $asp->boot();
    }

    public function testSetPermissionsForUser()
    {
        $this->user = User::factory()->create([
            'is_administrator' => false,
        ]);
        // Set the URL & permission to test.
        $url = '/designer/scripts';
        $permission = 'view-scripts';

        // Our user has no permissions, so this should return 403.
        $response = $this->webCall('GET', $url);
        $response->assertStatus(403);

        // Attach the permission to our user.
        $this->user->permissions()->attach(Permission::byName($permission)->id);
        $this->user->refresh();

        // Our user now has permissions, so this should return 200.
        $response = $this->webCall('GET', $url);
        $response->assertStatus(200);
    }

    public function testSetPermissionsForGroup()
    {
        $this->user = User::factory()->create([
            'is_administrator' => false,
        ]);
        // Create a group.
        $group = Group::factory()->create([
            'name' => 'Test Permissions',
        ]);

        // Assign our user to the group.
        GroupMember::factory()->create([
            'group_id' => $group->id,
            'member_type' => User::class,
            'member_id' => $this->user->id,
        ]);

        // Set the URL & permission to test.
        $url = '/designer/screens';
        $permission = 'view-screens';

        // Our group has no permissions, so this should return 403.
        $response = $this->webCall('GET', $url);
        $response->assertStatus(403);

        // Attach the permission to our group.
        $group->permissions()->attach(Permission::byName($permission)->id);
        $this->user->refresh();

        // Our group now has permission, so this should return 200.
        $response = $this->webCall('GET', $url);
        $response->assertStatus(200);
    }

    public function testAdminPermissions()
    {
        $this->user = User::factory()->create([
            'is_administrator' => false,
        ]);
        // Set the URL & permission to test.
        $url = '/designer/environment-variables';
        $permission = 'view-environment_variables';

        // Our user has no permission for this, so this should return 403.
        $response = $this->webCall('GET', $url);
        $response->assertStatus(403);

        // Make the user an admin.
        $this->user->is_administrator = true;

        // Our user now has permission, so this should return 200.
        $response = $this->webCall('GET', $url);
        $response->assertStatus(200);
    }

    public function testCreatePermission()
    {
        $attributes = [
            'name' => 'create-package_permissions',
            'title' => 'Create Package Permissions',
            'group' => 'Package Permissions',
        ];

        $this->assertDatabaseMissing('permissions', $attributes);

        Permission::factory()->create($attributes);

        $this->assertDatabaseHas('permissions', $attributes);
    }
}
