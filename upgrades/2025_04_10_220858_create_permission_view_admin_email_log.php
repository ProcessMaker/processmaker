<?php

use ProcessMaker\Models\Permission;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class CreatePermissionViewAdminEmailLog extends Upgrade
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Permission::updateOrCreate(
            [
                'name' => 'view-admin-email-log',
            ],
            [
                'title' => 'View Email Log',
                'name' => 'view-admin-email-log',
                'group' => 'Admin Email Log',
            ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Permission::where('name', 'view-admin-email-log')->delete();
    }
}
