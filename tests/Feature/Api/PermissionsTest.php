<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\PermissionAssignment;
use Tests\Feature\Shared\RequestHelper;

class PermissionsTest extends TestCase
{
    use RequestHelper;
    
    protected function withUserSetup()
    {
        $create_process_perm = Permission::byGuardName('processes.create');
        $show_process_perm   = Permission::byGuardName('processes.show');
        $update_process_perm = Permission::byGuardName('processes.update');

        $admin_group = $this->admin_group =
            factory(Group::class)->create(['name' => 'Admin']);
        $super_admin_group =
            factory(Group::class)->create(['name' => 'Super Admin']);
        
        factory(GroupMember::class)->create([
            'member_uuid' => $this->user->uuid,
            'member_type' => User::class,
            'group_uuid'  => $super_admin_group->uuid,
        ]);

        factory(GroupMember::class)->create([
            'member_uuid' => $super_admin_group->uuid,
            'member_type' => Group::class,
            'group_uuid'  => $admin_group->uuid,
        ]);

        factory(PermissionAssignment::class)->create([
            'assignable_type' => Group::class,
            'assignable_uuid' => $admin_group->uuid,
            'permission_uuid' => $create_process_perm->uuid,
        ]);

        factory(PermissionAssignment::class)->create([
            'assignable_type' => Group::class,
            'assignable_uuid' => $super_admin_group->uuid,
            'permission_uuid' => $update_process_perm->uuid,
        ]);

        factory(PermissionAssignment::class)->create([
            'assignable_type' => get_class($this->user),
            'assignable_uuid' => $this->user->uuid,
            'permission_uuid' => $show_process_perm->uuid,
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
        
        $response = $this->apiCall('GET', '/processes/' . $this->process->uuid_text);
        $response->assertStatus(200);
        
        $destroy_process_perm = Permission::byGuardName('processes.destroy');
        Group::where('name', 'All Permissions')
            ->firstOrFail()
            ->permissionAssignments()
            ->where('permission_uuid', $destroy_process_perm->uuid)
            ->delete();

        $this->user->refresh();
        $this->flushSession();

        $response = $this->apiCall('DELETE', '/processes/' . $this->process->uuid_text);
        $response->assertStatus(403);
        $response->assertSee('Not authorized');

        factory(PermissionAssignment::class)->create([
            'assignable_type' => Group::class,
            'assignable_uuid' => $this->admin_group->uuid,
            'permission_uuid' => Permission::byGuardName('processes.destroy')->uuid,
        ]);

        $this->user->refresh();
        $this->flushSession();

        $response = $this->apiCall('DELETE', '/processes/' . $this->process->uuid_text);
        $response->assertStatus(204);
    }
}