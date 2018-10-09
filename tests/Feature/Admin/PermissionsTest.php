<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\PermissionAssignment;
use Tests\Feature\Shared\RequestHelper;

class GroupTest extends TestCase
{
    use DatabaseTransactions;
    use RequestHelper;

    protected function buildPermissions()
    {
        $this->user = factory(User::class)->create(['password' => 'password']);

        $create_process_perm = factory(Permission::class)->create([
            'guard_name' => 'processes.create',
        ]);
        $show_process_perm = factory(Permission::class)->create([
            'guard_name' => 'processes.show',
        ]);
        $update_process_perm = factory(Permission::class)->create([
            'guard_name' => 'processes.update',
        ]);
        $delete_process_perm = factory(Permission::class)->create([
            'guard_name' => 'processes.delete',
        ]);

        $admin_group =
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
    }

    public function testPermissions()
    {
        $this->buildPermissions();
        $process = factory(\ProcessMaker\Models\Process::class)->create([
            'name' => 'foo',
        ]);
        $response = $this->webGet('/processes');
        $response->assertStatus(200);
    }
}