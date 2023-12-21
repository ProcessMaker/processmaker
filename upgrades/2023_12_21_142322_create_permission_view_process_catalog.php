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
        if (!Permission::where('name', 'view-process-catalog')->first()) {
            Permission::factory()->create([
                'title' => 'View Process Catalog',
                'name' => 'view-process-catalog',
                'group' => 'Process Catalog',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($permission = Permission::where('name', 'view-process-catalog')->first()) {
            $permission->delete();
        }
    }
}
