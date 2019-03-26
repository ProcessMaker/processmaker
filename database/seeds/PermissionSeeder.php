<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;

class PermissionSeeder extends Seeder
{
    private $permissionGroups = [
        'Processes' => [
            'archive-processes',
            'create-processes',
            'edit-processes',
            'export-processes',
            'import-processes',
            'view-processes',
        ],
        'Categories' => [
            'create-categories',
            'delete-categories',
            'edit-categories',
            'view-categories',
        ],
        'Comments' => [
            'create-comments',
            'delete-comments',
            'edit-comments',
            'view-comments',
        ],
        'Environment Variables' => [
            'create-environment_variables',
            'delete-environment_variables',
            'edit-environment_variables',
            'view-environment_variables',
        ],
        'Groups' => [
            'create-groups',
            'delete-groups',
            'edit-groups',
            'view-groups',
        ],
        'Screens' => [
            'create-screens',
            'delete-screens',
            'edit-screens',
            'view-screens',
        ],
        'Scripts' => [
            'create-scripts',
            'delete-scripts',
            'edit-scripts',
            'view-scripts',
        ],
        'Users' => [
            'create-users',
            'delete-users',
            'edit-users',
            'view-users',
        ],
        'Requests' => [
            'view-all_requests',
            'edit-request_data',
            'edit-task_data',
        ],
        'Files (API)' => [
            'create-files',
            'view-files',
            'edit-files',
            'delete-files',
        ],
        'Notifications (API)' => [
            'create-notifications',
            'view-notifications',
            'edit-notifications',
            'delete-notifications',
        ],
        'Task Assignments (API)' => [
            'create-task_assignments',
            'view-task_assignments',
            'edit-task_assignments',
            'delete-task_assignments',
        ],
        'Auth Clients' => [
            'create-auth_clients',
            'view-auth_clients',
            'edit-auth_clients',
            'delete-auth_clients',
        ],
    ];

    public function run($seedUser = null)
    {
        if (Permission::count() === 0) {
            foreach ($this->permissionGroups as $groupName => $permissions) {
                foreach ($permissions as $permissionString) {
                    $permission = factory(Permission::class)->create([
                        'title' => ucwords(preg_replace('/(\-|_)/', ' ',
                                $permissionString)),
                        'name' => $permissionString,
                        'group' => $groupName,
                    ]);
                }
            }
        }

        $permissions = Permission::all()->pluck('id');

        if ($seedUser) {
            $seedUser->permissions()->attach($permissions);
            $seedUser->save();
        }
    }
}
