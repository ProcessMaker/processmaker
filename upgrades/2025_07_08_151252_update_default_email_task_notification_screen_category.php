<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;

class UpdateDefaultEmailTaskNotificationScreenCategory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Find the default email task notification screen
        $screen = Screen::where('key', 'default-email-task-notification')->first();

        if ($screen) {
            // Remove the screen from the System category
            $systemCategory = ScreenCategory::where('is_system', 1)->first();

            if ($systemCategory) {
                $screen->categories()->detach($systemCategory->id);
            }

            // Set screen_category_id to null to remove any category association
            $screen->update(['screen_category_id' => null, 'title' => 'Default Email Task Notification', 'is_default' => 1]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Find the default email task notification screen
        $screen = Screen::where('key', 'default-email-task-notification')->first();

        if ($screen) {
            // Re-add the screen to the System category
            $systemCategory = ScreenCategory::where('is_system', 1)->first();

            if ($systemCategory) {
                $screen->categories()->attach($systemCategory->id);
                $screen->update(['screen_category_id' => $systemCategory->id, 'title' => 'DEFAULT_EMAIL_TASK_NOTIFICATION']);
            }
        }
    }
}
