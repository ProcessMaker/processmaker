<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;

class PermissionSeeder extends Seeder
{
    private $permissions = [
        'view-all_requests',
        'view-scripts',
        'create-scripts',
        'edit-scripts',
        'delete-scripts',
        'create-categories',
        'view-categories',
        'edit-categories',
        'delete-categories',
        'view-screens',
        'create-screens',
        'edit-screens',
        'delete-screens',
        'create-environment_variables',
        'view-environment_variables',
        'edit-environment_variables',
        'delete-environment_variables',
        'view-processes',
        'create-processes',
        'edit-processes',
        'archive-processes',
        'view-users',
        'create-users',
        'edit-users',
        'delete-users',
        'create-groups',
        'view-groups',
        'edit-groups',
        'delete-groups',
        'create-comments',
        'view-comments',
        'edit-comments',
        'delete-comments',
    ];

    private $resourcePermissions = [
        'requests'
    ];

    public function run(User $user = null)
    {
        if (Permission::count() === 0) {
            $group = factory(Group::class)->create([
                'name' => 'All Permissions',
            ]);

            if ($user = User::first()) {
                factory(GroupMember::class)->create([
                    'group_id' => $group->id,
                    'member_type' => User::class,
                    'member_id' => $user->id,
                ]);
            }

            foreach ($this->permissions as $permissionString) {
                $permission = factory(Permission::class)->create([
                    'title' => ucwords(preg_replace('/(\-|_)/', ' ',
                            $permissionString)),
                    'name' => $permissionString,
                ]);
            }
        }

        if ($user) {
            $permissions = Permission::all()->pluck('id');
            $user->permissions()->attach($permissions);
            $user->save();
        }
    }
}
