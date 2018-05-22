<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Model\Permission;

/**
 * Generate the initial Permissions that ProcessMaker needs
 */
class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Permission::class)->create([
            'code' => 'PM_FACTORY'
        ]);
        factory(Permission::class)->create([
            'code' => 'PM_CASES'
        ]);
        factory(Permission::class)->create([
            'code' => 'PM_SETUP_PROCESS_CATEGORIES'
        ]);
        factory(Permission::class)->create([
            'code' => 'PM_SETUP_PM_TABLES'
        ]);
        factory(Permission::class)->create([
            'code' => 'PM_USERS'
        ]);
    }
}
