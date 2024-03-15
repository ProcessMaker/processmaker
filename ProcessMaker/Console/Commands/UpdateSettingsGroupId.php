<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Log;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\SettingsMenus;

class UpdateSettingsGroupId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:update-settings-group-id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the column group_id in settings';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Update the setting.group_id with the corresponding category created in settings_menus
        Setting::updateAllSettingsGroupId();
        return $this->info("Settings group_id updated successfully");
    }
}
