<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;

class GroupSeeder extends Seeder
{
    public $defaults = [];

    public function setDefaults()
    {
        $this->defaults[] = [
            'name' => __('Requesters'),
            'description' => __('Users can view and start new requests.'),
            'permissions' => [
                'view-all_requests',
                'view-users',
                'view-groups',
                'view-comments',
                'create-comments',
                'edit-comments',
            ],
        ];

        $this->defaults[] = [
            'name' => __('Process Designers'),
            'description' => __('Users can design processes.'),
            'permissions' => [
                'view-all_requests',
                'view-processes',
                'create-processes',
                'edit-processes',
                'archive-processes',
                'view-categories',
                'create-categories',
                'edit-categories',
                'delete-categories',
                'view-screens',
                'create-screens',
                'edit-screens',
                'delete-screens',
                'view-scripts',
                'create-scripts',
                'edit-scripts',
                'delete-scripts',
                'view-environment_variables',
                'view-users',
                'view-groups',
            ],
        ];

        $this->defaults[] = [
            'name' => __('Administrators'),
            'description' => __('Users can administrate users, groups, and auth clients.'),
            'permissions' => [
                'view-users',
                'create-users',
                'edit-users',
                'delete-users',
                'view-groups',
                'create-groups',
                'edit-groups',
                'delete-groups',
                'view-auth_clients',
                'create-auth_clients',
                'edit-auth_clients',
                'delete-auth_clients',
                'delete-comments'
            ],
        ];
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->setDefaults();

        foreach ($this->defaults as $defaultGroup) {
            // Create the group
            $createdGroup = factory(Group::class)->create([
                'name' => $defaultGroup['name'],
                'description' => $defaultGroup['description'],
                'status' => 'ACTIVE'
            ]);

            //Retrieve permission IDs
            $permissions = Permission::byName($defaultGroup['permissions'])->pluck('id');

            //Attach permissions to this group
            $createdGroup->permissions()->attach($permissions);
        }

    }
}
