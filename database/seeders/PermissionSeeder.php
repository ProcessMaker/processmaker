<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\User;

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
            'create-process-categories',
            'delete-process-categories',
            'edit-process-categories',
            'view-process-categories',
        ],
        'Process Templates' => [
            'delete-process-templates',
            'create-process-templates',
            'edit-process-templates',
            'export-process-templates',
            'import-process-templates',
            'view-process-templates',
        ],
        'Process Translations' => [
            'create-process-translations',
            'view-process-translations',
            'import-process-translations',
            'export-process-translations',
            'edit-process-translations',
            'cancel-process-translations',
            'delete-process-translations',
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
            'export-screens',
            'import-screens',
            'create-screen-categories',
            'delete-screen-categories',
            'edit-screen-categories',
            'view-screen-categories',
        ],
        'Scripts' => [
            'create-scripts',
            'delete-scripts',
            'edit-scripts',
            'view-scripts',
            'create-script-categories',
            'delete-script-categories',
            'edit-script-categories',
            'view-script-categories',
        ],
        'Users' => [
            'create-users',
            'delete-users',
            'edit-users',
            'view-users',
            'view-other-users-profiles',
            'edit-personal-profile',
            'publish-screen-templates',
        ],
        'Username and Password' => [
            'edit-user-and-password',
        ],
        'Requests' => [
            'view-all_cases',
            'view-all_requests',
            'view-my_requests',
            'edit-request_data',
            'edit-task_data',
        ],
        'Files (API)' => [
            'create-files',
            'view-files',
            'edit-files',
            'delete-files',
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
        'Signals' => [
            'create-signals',
            'view-signals',
            'edit-signals',
            'delete-signals',
        ],
    ];

    private $defaultPermissions = [
        'view-my_requests',
    ];

    public function run($seedUser = null)
    {
        foreach ($this->permissionGroups as $groupName => $permissions) {
            foreach ($permissions as $permissionString) {
                $permission = Permission::updateOrCreate([
                    'name' => $permissionString,
                ], [
                    'title' => ucwords(preg_replace('/(\-|_)/', ' ', $permissionString)),
                    'group' => $groupName,
                ]);

                if (in_array($permissionString, $this->defaultPermissions)) {
                    $this->assignDefaultPermission($permission);
                }
            }
        }

        $permissions = Permission::all()->pluck('id');

        if ($seedUser) {
            $seedUser->permissions()->attach($permissions);
            $seedUser->save();
        }
    }

    /**
     * Assign default permission to users and groups.
     */
    private function assignDefaultPermission(Permission $permission): void
    {
        $userIds = User::nonSystem()->pluck('id');
        $groupIds = Group::pluck('id');
    
        // Define the chunk size for the permission assignment
        $chunkSize = 500;
    
        // Attach user IDs in chunks
        $userIds->chunk($chunkSize)->each(function ($chunk) use ($permission) {
            $permission->users()->attach($chunk);
        });
    
        // Attach group IDs in chunks
        $groupIds->chunk($chunkSize)->each(function ($chunk) use ($permission) {
            $permission->groups()->attach($chunk);
        });
    }
}
