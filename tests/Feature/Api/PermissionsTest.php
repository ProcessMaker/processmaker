<?php

namespace Tests\Feature\Api;

use Database\Seeders\PermissionSeeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\AuthServiceProvider;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use RequestHelper;

    protected function withUserSetup()
    {
        $this->user->is_administrator = false;
        $this->user->save();

        // Seed our tables.
        Artisan::call('db:seed', ['--class' => 'PermissionSeeder']);

        // Reboot our AuthServiceProvider. This is necessary so that it can
        // pick up the new permissions and setup gates for each of them.
        $asp = new AuthServiceProvider(app());
        $asp->boot();
    }

    public function testApiPermissions()
    {
        $group = Group::factory()->create([
            'name' => 'All Permissions',
        ]);
        $permissions = Permission::all()->pluck('id');
        $group->permissions()->attach($permissions);
        $group->save();

        GroupMember::factory()->create([
            'group_id' => $group->id,
            'member_type' => User::class,
            'member_id' => $this->user->id,
        ]);

        $this->user->refresh();
        $this->flushSession();

        $process = Process::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'ACTIVE',
        ]);

        $response = $this->apiCall('GET', '/processes');
        $response->assertStatus(200);

        $response = $this->apiCall('GET', '/processes/' . $process->id);
        $response->assertStatus(200);

        $permission = Permission::byName('archive-processes');
        $group = Group::where('name', 'All Permissions')->firstOrFail();
        $group->permissions()->detach($permission->id);
        $this->user->refresh();
        $this->flushSession();

        $response = $this->apiCall('DELETE', '/processes/' . $process->id);
        $response->assertStatus(403);

        $this->user->permissions()->attach($permission->id);
        $this->user->refresh();
        $this->flushSession();

        $response = $this->apiCall('DELETE', '/processes/' . $process->id);
        $response->assertStatus(204);
    }

    public function testSetPermissionsForUser()
    {
        $this->user = User::factory()->create([
            'password' => Hash::make('password'),
            'is_administrator' => true,
        ]);

        $testUser = User::factory()->create();
        $testPermission = Permission::factory()->create();
        $response = $this->apiCall('PUT', '/permissions', [
            'user_id' => $testUser->id,
            'permission_names' => [$testPermission->name],
        ]);

        $response->assertStatus(204);

        //Assert that the permissions has been set
        $this->assertEquals($testUser->permissions->count(), 1);
        $this->assertEquals($testUser->permissions->first()->id, $testPermission->id);
    }

    public function testSetPermissionsViewProcessCatalogForUser()
    {
        $faker = Faker::create();
        Permission::updateOrCreate([
            'name' => 'view-process-catalog',
        ], [
            'title' => 'View Process Catalog',
            'name' => 'view-process-catalog',
            'group' => 'Process Catalog',
        ]);
        $testUser = User::updateOrCreate([
            'username' => 'will',
            'is_administrator' => true,
        ], [
            'username' => 'will',
            'password' => $faker->password(8) . 'A' . '1' . '+',
            'email' => $faker->email(),
            'firstname' => $faker->firstName(),
            'lastname' => $faker->lastName(),
        ]);
        // Assert that the permissions has been set
        $this->assertTrue($testUser->hasPermission('view-process-catalog'));
        // Clean
        session(['permissions' => null]);
    }

    public function testCategoryPermission()
    {
        $context = function ($type, $class) {
            $attrs = ['name' => 'Test Category', 'status' => 'ACTIVE'];
            $url = route("api.{$type}_categories.store");
            $response = $this->apiCall('POST', $url, $attrs);
            $response->assertStatus(403);

            // Now give the user permission via a group
            $group = Group::factory()->create([
                'name' => 'Test',
            ]);
            GroupMember::factory()->create([
                'group_id' => $group->id,
                'member_type' => User::class,
                'member_id' => $this->user->id,
            ]);

            $permission = Permission::byName("create-{$type}-categories");
            $group->permissions()->attach($permission);
            $this->user->refresh();
            $this->flushSession();

            // test create permission
            $response = $this->apiCall('POST', $url, $attrs);
            $response->assertStatus(201);

            // test update permission
            $id = $response->json()['id'];
            $attrs = ['name' => 'Test Category Update', 'status' => 'ACTIVE'];
            $url = route("api.{$type}_categories.update", $id);
            $response = $this->apiCall('PUT', $url, $attrs);
            $response->assertStatus(403);

            $permission = Permission::byName("edit-{$type}-categories");
            $group->permissions()->attach($permission);
            $this->user->refresh();
            $this->flushSession();

            $response = $this->apiCall('PUT', $url, $attrs);
            $this->assertEquals('Test Category Update', $class::find($id)->name);

            // test view permission
            $url = route("api.{$type}_categories.index");
            $response = $this->apiCall('GET', $url);
            $response->assertStatus(403);

            $url = route("api.{$type}_categories.show", $id);
            $response = $this->apiCall('GET', $url);
            $response->assertStatus(403);

            $permission = Permission::byName("view-{$type}-categories");
            $group->permissions()->attach($permission);
            $this->user->refresh();
            $this->flushSession();

            $url = route("api.{$type}_categories.index");
            $response = $this->apiCall('GET', $url);
            $response->assertStatus(200);

            $url = route("api.{$type}_categories.show", $id);
            $response = $this->apiCall('GET', $url);
            $response->assertStatus(200);

            // test delete permission
            $url = route("api.{$type}_categories.destroy", $id);
            $response = $this->apiCall('DELETE', $url);
            $response->assertStatus(403);

            $permission = Permission::byName("view-{$type}-categories");
            $group->permissions()->attach($permission);
            $this->user->refresh();
            $this->flushSession();

            $response = $this->apiCall('DELETE', $url);
            $response->assertStatus(403);
        };

        $context('process', ProcessCategory::class);
        $context('script', ScriptCategory::class);
        $context('screen', ScreenCategory::class);
    }

    /**
     * Test if the created event in UserObserver assigns the correct permissions.
     */
    public function testSetPermissionsViewMyRequestForUser()
    {
        $permissionName = 'view-my_requests';
        $permissionTitle = 'View My Requests';
        // Ensure permission is created without duplicates
        Permission::firstOrCreate(['name' => $permissionName, 'title' => $permissionTitle]);

        // Create a user (this should trigger the observer)
        $user = User::factory()->create();

        // Assert the user has been assigned the correct permissions
        $this->assertTrue($user->permissions()->where('name', $permissionName)->exists());
    }

    /**
     * Test that the permissions are seeded and assigned to users and groups.
     */
    public function testSetPermissionsViewMyRequestForUsersAndGroupCreated()
    {
        //Set up the users and groups
        $users = User::factory()->count(5)->create();
        $groups = Group::factory()->count(3)->create();

        //Run the seeder
        $this->seed(PermissionSeeder::class);
        $permissionName = 'view-my_requests';
        $permissionTitle = 'View My Requests';

        //Verify that the permission exists
        $this->assertDatabaseHas('permissions', [
            'name' => $permissionName,
            'group' => 'Cases and Requests',
            'title' => $permissionTitle,
        ]);

        //Verify that the permission is assigned to users
        $permission = Permission::where('name', $permissionName)->first();
        $this->assertNotNull($permission);
        foreach ($users as $user) {
            $this->assertTrue($user->hasPermission($permissionName));
        }

        //Verify that the permission is assigned to groups
        foreach ($groups as $group) {
            $this->assertTrue($permission->groups->contains($group));
        }
    }
}
