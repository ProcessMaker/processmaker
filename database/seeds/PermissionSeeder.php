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
            'PER_CODE' => 'PM_FACTORY'
        ]);
        factory(Permission::class)->create([
            'PER_CODE' => 'PM_CASES'
        ]);
        factory(Permission::class)->create([
            'PER_CODE' => 'PM_SETUP_PROCESS_CATEGORIES'
        ]);
        factory(Permission::class)->create([
            'PER_CODE' => 'PM_SETUP_PM_TABLES'
        ]);
    }
}
