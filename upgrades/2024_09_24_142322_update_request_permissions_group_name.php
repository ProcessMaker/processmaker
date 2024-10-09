<?php

use ProcessMaker\Models\Permission;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class UpdateRequestPermissionsGroupName extends Upgrade
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Permission::where('group', 'Requests')
        ->update(['group' => 'Cases and Requests']);
    }
}
