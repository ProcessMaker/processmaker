<?php

use ProcessMaker\Models\Permission;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class CreatePermissionViewProcessCatalog extends Upgrade
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Permission::updateOrCreate([
            'name' => 'view-process-catalog',
        ], [
            'title' => 'View Process Catalog',
            'name' => 'view-process-catalog',
            'group' => 'Process Catalog',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::where('name', 'view-process-catalog')->delete();
    }
}
