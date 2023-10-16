<?php

use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\Permission;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class RemoveNotificationPermissions extends Upgrade
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 71-74 & 28-31
        $toDelete = [
            'create-comments',
            'delete-comments',
            'edit-comments',
            'view-comments',
            'create-notifications',
            'view-notifications',
            'edit-notifications',
            'delete-notifications',
        ];
        $permissions = Permission::whereIn('name', $toDelete);
        DB::table('assignables')->whereIn('permission_id', $permissions->pluck('id'))->delete();
        $permissions->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
}
