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
            'code' => 'PROCESSMAKER_ADMIN'
        ]);
        // Assign permissions
        $role->permissions()->attach(
            Permission::whereIn('code', [
                Permission::PM_SETUP_PROCESS_CATEGORIES,
                Permission::PM_FACTORY,
                Permission::PM_SETUP_PM_TABLES,
                Permission::PM_CASES
            ])->get()
        );


        $role = factory(Role::class)->create([
            'code' => 'PROCESSMAKER_OPERATOR'
        ]);

        factory(Role::class)->create([
            'code' => 'PROCESSMAKER_MANAGER'
        ]);
        factory(Role::class)->create([
            'code' => 'PROCESSMAKER_GUEST'
        ]);
    }
}
