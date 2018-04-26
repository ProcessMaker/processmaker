<?php

use Illuminate\Database\Seeder;

use ProcessMaker\Model\Role;
use ProcessMaker\Model\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Generate the appropriate initial roles for ProcessMaker
        $role = factory(Role::class)->create([
            'ROL_CODE' => 'PROCESSMAKER_ADMIN'
        ]);
        // Assign permissions
        $role->permissions()->attach(
            Permission::whereIn('PER_CODE', [
                Permission::PM_SETUP_PROCESS_CATEGORIES,
                Permission::PM_FACTORY,
                Permission::PM_SETUP_PM_TABLES,
                Permission::PM_CASES
            ])->get()
        );


        $role = factory(Role::class)->create([
            'ROL_CODE' => 'PROCESSMAKER_OPERATOR'
        ]);

        factory(Role::class)->create([
            'ROL_CODE' => 'PROCESSMAKER_MANAGER'
        ]);
        factory(Role::class)->create([
            'ROL_CODE' => 'PROCESSMAKER_GUEST'
        ]);
    }
}
