<?php

use ProcessMaker\Models\Permission;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class CreatePermissionViewAllCasesAllRequests extends Upgrade
{
    private const NEW_NAME_GROUP = 'Cases and Requests';

    private const PERMISSIONS = [
        [
            'name' => 'view-all_cases',
            'title' => 'View All Cases',
        ],
        [
            'name' => 'view-my_requests',
            'title' => 'View My Requests',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the group Requests to Cases and Requests
        DB::table('permissions')
            ->where('group', 'Requests')
            ->update(['group' => self::NEW_NAME_GROUP]);
        // Update the group Cases to Cases and Requests
        DB::table('permissions')
            ->where('group', 'Cases')
            ->update(['group' => self::NEW_NAME_GROUP]);
        // Update with the correct label "View All Request"
        // Summer 2024: PO requested change to "View All Cases"
        // Fall 2024: PO requested revert to "View All Request"
        $permission = Permission::where('name', 'view-all_requests')->first();
        if ($permission && $permission->title == 'View All Cases') {
            $permission->title = 'View All Request';
            $permission->save();
        }
        // Create new permissions [view-all_cases, view-all_requests]
        $this->createPermissions();
    }

    /**
     * Create new permissions
     *
     * @return void
     */
    private function createPermissions(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::updateOrCreate([
                'name' => $permission['name'],
            ], [
                'title' => $permission['title'],
                'name' => $permission['name'],
                'group' => self::NEW_NAME_GROUP,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::where('name', 'view-all_cases')->delete();
        Permission::where('name', 'view-my_requests')->delete();
    }
}
