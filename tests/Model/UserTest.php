<?php
namespace Tests\Model;

use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\PermissionAssignment;
use ProcessMaker\Providers\AuthServiceProvider;

class UserTest extends TestCase
{

    public function testPermissions() {
        $president_user = factory(User::class)->create(['password' => Hash::make('password')]);
        $technician_user = factory(User::class)->create(['password' => Hash::make('password')]);
        $mom_user = factory(User::class)->create(['password' => Hash::make('password')]);

        $ln_permission = factory(Permission::class)->create([
            'name' => 'launch.nukes',
        ]);
        $dn_permission = factory(Permission::class)->create([
            'name' => 'disarm.nukes',
        ]);

        $nl_group = factory(Group::class)->create(['name' => 'Nuke Launchers']);
        $p_group = factory(Group::class)->create(['name' => 'Presidents']);

        // TODO: Make this work with attach
        // factory(GroupMember::class)->create([
        //     'group_id' => $nl_group->id,
        //     'member_type' => Group::class,
        //     'member_id' => $p_group,
        // ]);

        // $nl_group->childGroups()->attach($p_group);

        factory(GroupMember::class)->create([
            'group_id' => $nl_group->id,
            'member_type' => User::class,
            'member_id' => $technician_user,
        ]);
        
        factory(GroupMember::class)->create([
            'group_id' => $p_group->id,
            'member_type' => User::class,
            'member_id' => $president_user->id,
        ]);

        $p_group->permissions()->attach($ln_permission);
        $nl_group->permissions()->attach($dn_permission);
        $mom_user->permissions()->attach($dn_permission);

        $this->assertTrue($president_user->hasPermission('launch.nukes'));

        // TODO: Groups belong to groups
        // $this->assertTrue($president_user->hasPermission('disarm.nukes'));

        $this->assertFalse($technician_user->hasPermission('launch.nukes'));
        $this->assertTrue($technician_user->hasPermission('disarm.nukes'));

        $this->assertTrue($mom_user->hasPermission('disarm.nukes'));
        $this->assertFalse($mom_user->hasPermission('launch.nukes'));
    }

    public function testCanAny()
    {
        $user = factory(User::class)->create();
        
        $p1 = factory(Permission::class)->create(['name' => 'foo']);
        $p2 = factory(Permission::class)->create(['name' => 'bar']);
        $p3 = factory(Permission::class)->create(['name' => 'baz']);
        
        (new AuthServiceProvider(app()))->boot();

        $this->assertFalse($user->can('bar'));
        $this->assertFalse($user->canAny('foo|bar'));
        
        $user->permissions()->attach($p2);
        $user->permissions()->attach($p3);
        $user->refresh();

        $this->assertTrue($user->can('bar'));
        $this->assertEquals('bar', $user->canAny('foo|bar'));
        $this->assertEquals('baz', $user->canAny('foo|baz'));
    }
}