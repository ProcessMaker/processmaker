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
        'about.index',
        'about.menu',
        'admin.menu',
        'documents.create',
        'documents.destroy',
        'documents.edit',
        'documents.show',
        'documents.store',
        'documents.update',
        'environment_variables.create',
        'environment_variables.destroy',
        'environment_variables.edit',
        'environment_variables.show',
        'environment_variables.store',
        'environment_variables.update',
        'files.destroy',
        'files.show',
        'files.store',
        'files.update',
        'forms.create',
        'forms.destroy',
        'forms.edit',
        'forms.show',
        'forms.store',
        'forms.update',
        'group_members.destroy',
        'group_members.show',
        'group_members.store',
        'groups.create',
        'groups.destroy',
        'groups.edit',
        'groups.show',
        'groups.store',
        'groups.update',
        'notifications',
        'preferences.create',
        'preferences.destroy',
        'preferences.edit',
        'preferences.show',
        'preferences.store',
        'preferences.update',
        'process_categories.destroy',
        'process_categories.show',
        'process_categories.store',
        'process_categories.update',
        'process_events.trigger',
        'processes.create',
        'processes.destroy',
        'processes.edit',
        'processes.show',
        'processes.store',
        'processes.update',
        'processes.menu',
        'processes.cancel',
        'profile.edit',
        'profile.show',
        'requests.destroy',
        'requests.edit',
        'requests.show',
        'requests.store',
        'requests.update',
        'requests.watch',
        'requests.menu',
        'requests.menu_search',
        'script.preview',
        'scripts.create',
        'scripts.destroy',
        'scripts.edit',
        'scripts.show',
        'scripts.store',
        'scripts.update',
        'tasks.show',
        'tasks.update',
        'users.create',
        'users.destroy',
        'users.edit',
        'users.show',
        'users.store',
        'users.update',
        'users.menu',
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
