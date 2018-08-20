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
            'name' => 'Administrator',
            'description' => 'Overall Administration of System',
            'id' => Role::PROCESSMAKER_ADMIN
        ]);
        // Assign permissions
        $role->permissions()->attach(
            Permission::whereIn('code', [
                Permission::PM_SETUP_PROCESS_CATEGORIES,
                Permission::PM_FACTORY,
                Permission::PM_SETUP_PM_TABLES,
                Permission::PM_CASES,
                Permission::PM_USERS
            ])->get()
        );


        $role = factory(Role::class)->create([
            'name' => 'Operator',
            'description' => 'Standard Users that allow login and execution of cases',
            'id' => Role::PROCESSMAKER_OPERATOR
        ]);

        factory(Role::class)->create([
            'name' => 'Manager',
            'description' => 'Allow management of cases,users and groups.',
            'id' => Role::PROCESSMAKER_MANAGER
        ]);
   }
}
