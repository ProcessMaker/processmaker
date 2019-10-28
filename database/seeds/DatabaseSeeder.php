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
            PermissionSeeder::class,
            SystemCategorySeeder::class,
            GroupSeeder::class,
            ScreenTypeSeeder::class,
            InterstitialScreenSeeder::class
        ]);
        $this->callPluginSeeders();
    }
}
