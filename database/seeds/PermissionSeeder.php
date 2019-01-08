<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\PermissionAssignment;

class PermissionSeeder extends Seeder
{

    private $permissions = [
        'view-scripts',
        'create-scripts',
        'edit-scripts',
        'delete-scripts',
        'view-categories',
        'edit-categories',
        'view-screens',
        'edit-screens',
        'view-environment_variables',
        'edit-environment_variables',
        'view-processes',
        'view-users',
        'create-users',
        'show-users',
        'edit-users'

    ];

    private $resourcePermissions = [
        'requests'
    ];

    public function run($user = null)
    {
        if (Permission::count() !== 0) {
            return;
        }
        $group = factory(Group::class)->create([
            'name' => 'All Permissions',
        ]);

        if (!$user) {
            $user = User::first()->id;
        }

        factory(GroupMember::class)->create([
            'group_id' => $group->id,
            'member_type' => User::class,
            'member_id' => User::first()->id,
        ]);

        foreach ($this->permissions as $permissionString) {
            $permission = factory(Permission::class)->create([
                'title' => ucwords(preg_replace('/(\-|_)/', ' ',
                        $permissionString)),
                'name' => $permissionString,
            ]);
            
            factory(PermissionAssignment::class)->create([
                'permission_id' => $permission->id,
                'assignable_type' => Group::class,
                'assignable_id' => $group->id,
            ]);
        }
    }
}
