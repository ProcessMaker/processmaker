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
            $screen = Screen::where('title', $json[0]->name)->first();
            $systemCategory = ScreenCategory::where('is_system', true)->first();
            if ($screen && $systemCategory) {
                if ($screen->screen_category_id === $systemCategory->id) {
                    $screen->screen_category_id = null;
                    $screen->categories()->sync([]);
                    $screen->save();
                }
            }
            
            if (!$screen) {
                $screen = new Screen();
                $screen->fill([
                    'title' => $json[0]->name,
                    'description' => 'Screen for the interstitial',
                    'type' => 'DISPLAY',
                    'config' => $json,
                    'key' => 'interstitial',
                    'screen_category_id' => null 
                ]);
                $screen->save();
            }
            return $screen;
        }


    }
}
