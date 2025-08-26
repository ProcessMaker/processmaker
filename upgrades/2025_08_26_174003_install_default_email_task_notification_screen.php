<?php

use ProcessMaker\Models\Screen;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class InstallDefaultEmailTaskNotificationScreen extends Upgrade
{
    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        echo PHP_EOL . '    Installing default email task notification screen...' . PHP_EOL;

        // Obtener solo los IDs para mayor eficiencia
        $screenIds = Screen::where('key', 'default-email-task-notification')->pluck('id')->toArray();

        if (count($screenIds) > 0) {
            echo '    Found ' . count($screenIds) . " existing screen(s) with key 'default-email-task-notification'" . PHP_EOL;

            // Update por batch usando los IDs obtenidos directamente
            $updatedCount = Screen::whereIn('id', $screenIds)
                ->update([
                    'key' => 'default-email-task-notification-old',
                    'is_default' => 0,
                ]);

            echo "    Successfully renamed {$updatedCount} screen(s) to 'default-email-task-notification-old'" . PHP_EOL;
        } else {
            echo "    No existing screens found with key 'default-email-task-notification'" . PHP_EOL;
        }

        try {
            $screen = Screen::getScreenByKeyPerDefault('default-email-task-notification');

            if ($screen) {
                echo "    Successfully installed/updated screen: {$screen->title}" . PHP_EOL;
            } else {
                echo '    Warning: Screen installation returned null' . PHP_EOL;
            }
        } catch (Exception $e) {
            echo '    Error installing screen: ' . $e->getMessage() . PHP_EOL;
            throw $e;
        }

        echo PHP_EOL;
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        // Nothing to do here
    }
}
