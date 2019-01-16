<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use Tests\Feature\Shared\RequestHelper;
use \PermissionSeeder;

class PermissionsTest extends TestCase
{
    use RequestHelper;

    protected function withUserSetup()
    {
        $this->user->is_administrator = false;
        $this->user->save();
    }

    public function testApiPermissions()
    {
        $response = $this->apiCall('GET', '/processes');
        $response->assertStatus(200);

        $response = $this->apiCall('GET', '/processes/' . $this->process->id);
        $response->assertStatus(200);

        $destroy_process_perm = Permission::byName('processes.destroy');
        Group::where('name', 'All Permissions')
            ->firstOrFail()
            ->permissionAssignments()
            ->where('permission_id', $destroy_process_perm->id)
            ->delete();

        $this->user->refresh();
        $this->flushSession();

        $response = $this->apiCall('DELETE', '/processes/' . $this->process->id);
        $response->assertStatus(403);
        $response->assertSee('Not authorized');

        factory(PermissionAssignment::class)->create([
            'assignable_type' => Group::class,
            'assignable_id' => $this->admin_group->id,
            'permission_id' => Permission::byName('processes.destroy')->id,
        ]);

        $this->user->refresh();
        $this->flushSession();

        $response = $this->apiCall('DELETE', '/processes/' . $this->process->id);
        $response->assertStatus(204);
    }

    public function testSetPermissionsForUser()
    {
        $this->user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'is_administrator' => true,
        ]);

        $testUser = factory(User::class)->create();
        $testPermission = factory(Permission::class)->create();
        $response = $this->apiCall('PUT', '/permissions', [
            'user_id' => $testUser->id,
            'permission_names' => [$testPermission->name]
        ]);

        $response->assertStatus(204);

        //Assert that the permissions has benn set
        $this->assertEquals($testUser->permissions->count(), 1);
        $this->assertEquals($testUser->permissions->first()->id, $testPermission->id);
    }


    public function testRoutePermissionAliases()
    {
        
        // update route is an alias for edit permission
        $response = $this->apiCall('PUT', '/processes/' . $this->process->id, [
            'name' => 'foo',
            'description' => 'foo',
        ]);
        $response->assertStatus(200);
        
        // store route is an alias for create permission
        $response = $this->apiCall('POST', '/processes', [
            'name' => 'foo2',
            'description' => 'foo',
        ]);
        $response->assertStatus(201);
    }
}
