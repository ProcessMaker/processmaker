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
        'home',
        'documents.create',
        'documents.destroy',
        'documents.edit',
        'documents.show',
        'environment_variables.create',
        'environment_variables.destroy',
        'environment_variables.edit',
        'environment_variables.show',
        'forms.create',
        'forms.destroy',
        'forms.edit',
        'forms.show',
        'group_members.create',
        'group_members.destroy',
        'group_members.edit',
        'group_members.show',
        'groups.create',
        'groups.destroy',
        'groups.edit',
        'groups.show',
        'notifications',
        'preferences.edit',
        'process_categories.create',
        'process_categories.destroy',
        'process_categories.edit',
        'process_categories.show',
        'processes.create',
        'processes.destroy',
        'processes.edit',
        'processes.show',
        'profile.edit',
        'profile.show',
        'screens.create',
        'screens.destroy',
        'screens.edit',
        'screen.show',
        'scripts.create',
        'scripts.destroy',
        'scripts.edit',
        'scripts.show',
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

        foreach($this->permissions as $permissionString) {
            $permission = factory(Permission::class)->create([
                'name' => $permissionString,
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
