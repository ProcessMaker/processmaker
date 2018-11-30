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
        'documents.index',
        'documents.create',
        'documents.destroy',
        'documents.edit',
        'documents.show',
        'environment_variables.index',
        'environment_variables.create',
        'environment_variables.destroy',
        'environment_variables.edit',
        'environment_variables.show',
        'screens.index',
        'screens.create',
        'screens.destroy',
        'screens.edit',
        'screens.show',
        'group_members.index',
        'group_members.create',
        'group_members.destroy',
        'group_members.edit',
        'group_members.show',
        'groups.index',
        'groups.create',
        'groups.destroy',
        'groups.edit',
        'groups.show',
        'process_categories.index',
        'process_categories.create',
        'process_categories.destroy',
        'process_categories.edit',
        'process_categories.show',
        'processes.index',
        'processes.create',
        'processes.destroy',
        'processes.edit',
        'processes.show',
        'requests.index',
        'requests.create',
        'requests.destroy',
        'requests.cancel',
        'requests.edit',
        'requests.show',
        'scripts.index',
        'scripts.create',
        'scripts.destroy',
        'scripts.edit',
        'scripts.show',
        'users.index',
        'users.create',
        'users.destroy',
        'users.edit',
        'users.show'
    ];

    public function run($user = null)
    {
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
                'name' => ucwords(preg_replace('/(\.|_)/', ' ', $permissionString)),
                'guard_name' => $permissionString,
            ]);
            factory(PermissionAssignment::class)->create([
                'permission_id' => $permission->id,
                'assignable_type' => Group::class,
                'assignable_id' => $group->id,
            ]);
        }
    }
}
