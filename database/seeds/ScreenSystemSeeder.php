<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;

class ScreenSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $path = database_path('processes/screens/interstitial.json');
        if (file_exists($path)) {
            $json = json_decode(file_get_contents($path));
            return factory(Screen::class)->create([
                'title' => $json[0]->name,
                'description' => 'Screen for the interstitial',
                'type' => 'DISPLAY',
                'config' => $json,
                'key' => 'interstitial',
                'screen_category_id' => factory(ScreenCategory::class)->create([
                    'name' => 'System',
                    'is_system' => true
                ])->getKey()
            ]);
        }


    }
}
