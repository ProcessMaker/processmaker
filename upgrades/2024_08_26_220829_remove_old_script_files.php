<?php

use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class RemoveOldScriptFiles extends Upgrade
{
    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        // Remove old script input files (with prefix `put`)
        $path = config('app.processmaker_scripts_home');
        $files = glob($path . '/put*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        // Remove old script files with prefix `get`
        $path = config('app.processmaker_scripts_home');
        $files = glob($path . '/get*');
        $timeNow = time();

        foreach ($files as $file) {
            // Verify if the file was created 1 hour ago or more
            if (filemtime($file) <= ($timeNow - 3600)) {
                unlink($file);
            }
        }
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}

