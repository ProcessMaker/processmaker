<?php
namespace Tests\Model;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\PermissionAssignment;

class UserTest extends TestCase
{
    use DatabaseTransactions;

    public function testPermissions() {
        $president_user = factory(User::class)->create(['password' => 'password']);
        $technician_user = factory(User::class)->create(['password' => 'password']);
        $mom_user = factory(User::class)->create(['password' => 'password']);

        $ln_permission = factory(Permission::class)->create([
            'guard_name' => 'launch.nukes',
        ]);
        $dn_permission = factory(Permission::class)->create([
            'guard_name' => 'disarm.nukes',
        ]);

        $nl_group = factory(Group::class)->create(['name' => 'Nuke Launchers']);
        $p_group = factory(Group::class)->create(['name' => 'Presidents']);

        factory(GroupMember::class)->create([
            'group_uuid' => $nl_group->uuid,
            'member_type' => Group::class,
            'member_uuid' => $p_group,
        ]);
        
        factory(GroupMember::class)->create([
            'group_uuid' => $nl_group->uuid,
            'member_type' => User::class,
            'member_uuid' => $technician_user,
        ]);
        
        factory(GroupMember::class)->create([
            'group_uuid' => $p_group->uuid,
            'member_type' => User::class,
            'member_uuid' => $president_user->uuid,
        ]);

        factory(PermissionAssignment::class)->create([
            'permission_uuid' => $ln_permission->uuid,
            'assignable_type' => Group::class,
            'assignable_uuid' => $p_group->uuid,
        ]);
        
        factory(PermissionAssignment::class)->create([
            'permission_uuid' => $dn_permission->uuid,
            'assignable_type' => Group::class,
            'assignable_uuid' => $nl_group->uuid,
        ]);
        
        $mom_user->giveDirectPermission('disarm.nukes');

        $this->assertTrue($president_user->hasPermission('launch.nukes'));
        $this->assertTrue($president_user->hasPermission('disarm.nukes'));

        $this->assertFalse($technician_user->hasPermission('launch.nukes'));
        $this->assertTrue($technician_user->hasPermission('disarm.nukes'));

        $this->assertTrue($mom_user->hasPermission('disarm.nukes'));
        $this->assertFalse($mom_user->hasPermission('launch.nukes'));
    }
}