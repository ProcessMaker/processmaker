<?php

namespace Tests\Model;

use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\PermissionAssignment;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\AuthServiceProvider;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RequestHelper;

    public function testPermissions()
    {
        $president_user = User::factory()->create(['password' => Hash::make('password')]);
        $technician_user = User::factory()->create(['password' => Hash::make('password')]);
        $mom_user = User::factory()->create(['password' => Hash::make('password')]);

        $ln_permission = Permission::factory()->create([
            'name' => 'launch.nukes',
        ]);
        $dn_permission = Permission::factory()->create([
            'name' => 'disarm.nukes',
        ]);

        $nl_group = Group::factory()->create(['name' => 'Nuke Launchers']);
        $p_group = Group::factory()->create(['name' => 'Presidents']);

        GroupMember::factory()->create([
            'group_id' => $nl_group->id,
            'member_type' => User::class,
            'member_id' => $technician_user,
        ]);

        GroupMember::factory()->create([
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

    public function testCanAnyFirst()
    {
        $user = User::factory()->create();

        $p1 = Permission::factory()->create(['name' => 'foo']);
        $p2 = Permission::factory()->create(['name' => 'bar']);
        $p3 = Permission::factory()->create(['name' => 'baz']);

        (new AuthServiceProvider(app()))->boot();

        $this->assertFalse($user->can('bar'));
        $this->assertFalse($user->canAnyFirst('foo|bar'));

        $user->permissions()->attach($p2);
        $user->permissions()->attach($p3);
        $user->refresh();

        $this->assertTrue($user->can('bar'));
        $this->assertEquals('bar', $user->canAnyFirst('foo|bar'));
        $this->assertEquals('baz', $user->canAnyFirst('foo|baz'));
    }

    public function testAddCategoryViewPermissions()
    {
        $testFor = [
            'processes' => 'view-process-categories',
            'scripts' => 'view-script-categories',
            'screens' => 'view-screen-categories',
        ];

        $testFor = function ($singular, $plural) {
            $viewCatPerm = Permission::factory()->create(['name' => 'view-' . $singular . '-categories']);
            $editCatePerm = Permission::factory()->create(['name' => 'edit-' . $singular . '-categories']);

            foreach (['create', 'edit'] as $method) {
                $user = User::factory()->create();

                $perm = Permission::factory()->create(['name' => "{$method}-{$plural}"]);

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
