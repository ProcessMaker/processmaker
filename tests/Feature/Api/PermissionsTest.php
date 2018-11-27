<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\PermissionAssignment;
use Tests\Feature\Shared\RequestHelper;
use \PermissionSeeder;

class PermissionsTest extends TestCase
{
    use RequestHelper;

    protected function withUserSetup()
    {
        $this->user->is_administrator = false;
        $this->user->save();

        (new PermissionSeeder)->run($this->user);

        $create_process_perm = Permission::byGuardName('processes.create');
        $show_process_perm   = Permission::byGuardName('processes.show');
        $edit_process_perm = Permission::byGuardName('processes.edit');

        $admin_group = $this->admin_group =
            factory(Group::class)->create(['name' => 'Admin']);
        $super_admin_group =
            factory(Group::class)->create(['name' => 'Super Admin']);

        factory(GroupMember::class)->create([
            'member_id' => $this->user->id,
            'member_type' => User::class,
            'group_id'  => $super_admin_group->id,
        ]);

        factory(GroupMember::class)->create([
            'member_id' => $super_admin_group->id,
            'member_type' => Group::class,
            'group_id'  => $admin_group->id,
        ]);

        factory(PermissionAssignment::class)->create([
            'assignable_type' => Group::class,
            'assignable_id' => $admin_group->id,
            'permission_id' => $create_process_perm->id,
        ]);

        factory(PermissionAssignment::class)->create([
            'assignable_type' => Group::class,
            'assignable_id' => $super_admin_group->id,
            'permission_id' => $edit_process_perm->id,
        ]);

        $this->user->giveDirectPermission($show_process_perm->guard_name);

        $this->process = factory(\ProcessMaker\Models\Process::class)->create([
            'name' => 'foo',
        ]);
    }

    public function testApiPermissions()
    {
        $response = $this->apiCall('GET', '/processes');
        $response->assertStatus(200);

        $response = $this->apiCall('GET', '/processes/' . $this->process->id);
        $response->assertStatus(200);

        $destroy_process_perm = Permission::byGuardName('processes.destroy');
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
            'permission_id' => Permission::byGuardName('processes.destroy')->id,
        ]);

        $this->user->refresh();
        $this->flushSession();

        $response = $this->apiCall('DELETE', '/processes/' . $this->process->id);
        $response->assertStatus(204);
    }

    public function testSetPermissionsForUser()
    {
        $this->user = factory(User::class)->create([
            'password' => 'password',
            'is_administrator' => true,
        ]);

        $testUser = factory(User::class)->create();
        $testPermission = factory(Permission::class)->create();
        $response = $this->apiCall('PUT', '/permissions', [
            'user_id' => $testUser->id,
            'permission_ids' => [$testPermission->id]
        ]);

        $response->assertStatus(204);

        $updatedAssignments = PermissionAssignment::where('assignable_id', $testUser->id)
                                ->where('assignable_type', User::class)
                                ->get();

        //Assert that the permissions has benn set
        $this->assertEquals($updatedAssignments->count(), 1);
        $this->assertEquals($updatedAssignments->first()->permission_id, $testPermission->id);
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
