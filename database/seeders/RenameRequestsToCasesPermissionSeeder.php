<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\Permission;

class RenameRequestsToCasesPermissionSeeder extends Seeder
{
    public function run()
    {
        // Update the group Request by Cases
        DB::table('permissions')
            ->where('group', 'Requests')
            ->update(['group' => 'Cases']);
        // Update the title of group
        $permission = Permission::where('name', 'view-all_requests')->first();

        if ($permission) {
            $permission->title = 'View All Cases';
            $permission->save();
        }
    }
}
