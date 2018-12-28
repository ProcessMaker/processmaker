<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Traits\LoadPluginSeeders;

class DatabaseSeeder extends Seeder
{

    use LoadPluginSeeders;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UserSeeder::class,
            ProcessSeeder::class,
            PermissionSeeder::class
        ]);
        $this->callPluginSeeders();
    }
}
