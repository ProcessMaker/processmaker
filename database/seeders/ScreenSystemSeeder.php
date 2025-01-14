<?php

namespace Database\Seeders;

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
        // Create the Screen Interstitial
        $path = database_path('processes/screens/interstitial.json');
        $key = 'interstitial';
        $description = 'Screen for the interstitial';
        $this->createUpdateSystemScreen($path, 'DISPLAY', $key, $description);
        // Crear el screen email_task_notification
        $path = database_path('processes/screens/default-email-task-notification.json');
        $key = 'default-email-task-notification';
        $description = 'Screen for the email task notification';
        $this->createUpdateSystemScreen($path, 'EMAIL', $key, $description);
    }

    private function createUpdateSystemScreen($path, $type, $key, $description)
    {
        if (file_exists($path)) {
            $json = json_decode(file_get_contents($path));
            $screen = Screen::where('title', $json[0]->name)->first();
            $systemCategory = ScreenCategory::where('is_system', true)->first();
            if ($screen && $systemCategory && $screen->screen_category_id === $systemCategory->id) {
                $screen->screen_category_id = null;
                $screen->categories()->sync([]);
                $screen->save();
            }

            if (!$screen) {
                $screen = new Screen();
                $screen->fill([
                    'title' => $json[0]->name,
                    'description' => $description,
                    'type' => $type,
                    'config' => $json,
                    'key' => $key,
                    'screen_category_id' => null,
                ]);
                $screen->save();
            }

            return $screen;
        }
    }
}
