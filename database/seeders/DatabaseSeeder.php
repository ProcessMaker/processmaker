<?php

namespace Database\Seeders;

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
            AnonymousUserSeeder::class,
            PermissionSeeder::class,
            ProcessSystemCategorySeeder::class,
            GroupSeeder::class,
            ScreenTypeSeeder::class,
            CategorySystemSeeder::class,
            ScreenSystemSeeder::class,
            ScriptExecutorSeeder::class,
            SignalSeeder::class,
            SettingsMenusSeeder::class,
        ]);
        $this->callPluginSeeders();
    }
}
