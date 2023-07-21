<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;

class ScreenSystemSeeder extends Seeder
{
    const SCREENS_PATH = 'processes/screens/';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->installInterstitial();
        $this->installScreen('default-display-screen');
        $this->installScreen('default-form-screen');
    }

    /**
     * Install a screen.
     */
    private function installScreen(string $key): Screen
    {
        $path = database_path(self::SCREENS_PATH . "{$key}.json");
        $json = json_decode(file_get_contents($path), true);
        $screen = collect($json['screens'])->first();

        // By default, the screen is initially installed as a system screen.
        unset($screen['categories']);
        $screen['screen_category_id'] = null;

        return Screen::updateOrCreate([
            'key' => $screen['key'],
        ], $screen);
    }

    /**
     * Install the interstitial screen.
     */
    private function installInterstitial()
    {
        $path = database_path(self::SCREENS_PATH . 'interstitial.json');
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
                    'screen_category_id' => null,
                ]);
                $screen->save();
            }
        }
    }
}
