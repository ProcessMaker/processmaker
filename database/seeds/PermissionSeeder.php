<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;

class PermissionSeeder extends Seeder
{
    private $permissions = [
        'archive-processes',
        'create-categories',
        'create-comments',
        'create-environment_variables',
        'create-groups',
        'create-processes',
        'create-screens',
        'create-scripts',
        'create-users',
        'delete-categories',
        'delete-comments',
        'delete-environment_variables',
        'delete-groups',
        'delete-screens',
        'delete-scripts',
        'delete-users',
        'edit-categories',
        'edit-comments',
        'edit-environment_variables',
        'edit-groups',
        'edit-processes',
        'edit-screens',
        'edit-scripts',
        'edit-users',
        'view-all_requests',
        'view-auth-clients',
        'view-categories',
        'view-comments',
        'view-environment_variables',
        'view-groups',
        'view-processes',
        'view-screens',
        'view-scripts',
        'view-users',
    ];
    
    private $apiPermissions = [
        'create-users',
        'read-users',
        'update-users',
        'delete-users',
        'create-groups',
        'read-groups',
        'update-groups',
        'delete-groups',
        'read-group_users',
        'create-group_members',
        'read-group_members',
        'update-group_members',
        'delete-group_members',
        'create-environment_variables',
        'read-environment_variables',
        'update-environment_variables',
        'delete-environment_variables',
        'create-screens',
        'read-screens',
        'update-screens',
        'delete-screens',
        'create-screen_categories',
        'read-screen_categories',
        'update-screen_categories',
        'delete-screen_categories',
        'create-scripts',
        'read-scripts',
        'update-scripts',
        'delete-scripts',
        'create-processes',
        'read-processes',
        'update-processes',
        'delete-processes',
        'create-process_categories',
        'read-process_categories',
        'update-process_categories',
        'delete-process_categories',
        'update-permissions',
        'create-tasks',
        'read-tasks',
        'update-tasks',
        'delete-tasks',
        'create-requests',
        'read-requests',
        'update-requests',
        'delete-requests',
        'create-request_files',
        'read-request_files',
        'update-request_files',
        'delete-request_files',
        'create-process_events',
        'create-files',
        'read-files',
        'update-files',
        'delete-files',
        'create-notifications',
        'read-notifications',
        'update-notifications',
        'delete-notifications',
        'create-task_assignments',
        'read-task_assignments',
        'update-task_assignments',
        'delete-task_assignments',
        'create-comments',
        'read-comments',
        'update-comments',
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
            
            foreach ($this->apiPermissions as $permissionString) {
                $permission = factory(Permission::class)->create([
                    'title' => ucwords(preg_replace('/(\-|_)/', ' ',
                            $permissionString)),
                    'name' => 'api.' . $permissionString,
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
