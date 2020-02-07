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

    public function testAddCategoryViewPermissions()
    {
        $testFor = [
            'processes' => 'view-process-categories',
            'scripts' => 'view-script-categories',
            'screens' => 'view-screen-categories'
        ];

        $testFor = function($singular, $plural) {

            $viewCatPerm = factory(Permission::class)->create(['name' => 'view-' . $singular . '-categories']);
            $editCatePerm = factory(Permission::class)->create(['name' => 'edit-' . $singular . '-categories']);

            foreach (['create', 'edit'] as $method) {
                $user = factory(User::class)->create();
                
                $perm = factory(Permission::class)->create(['name' => "{$method}-{$plural}"]);
                
                (new AuthServiceProvider(app()))->boot();
                
                $this->assertFalse($user->can($perm->name));
                $this->assertFalse($user->can($viewCatPerm->name));

                $user->permissions()->attach($perm);
                $user->refresh();
                
                $this->assertTrue($user->can($viewCatPerm->name));
                $this->assertFalse($user->can($editCatePerm->name));
            }
        };

        $testFor('process', 'processes');
        $testFor('screen', 'screens');
        $testFor('script', 'scripts');

    }
}