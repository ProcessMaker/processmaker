<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\SettingsMenus;

return new class extends Migration {
    public const SETTINGS_GROUP = 'External Integrations';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Setting::where('group', self::SETTINGS_GROUP)->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $groupId = SettingsMenus::getId(SettingsMenus::INTEGRATIONS_MENU_GROUP);

        if ($groupId && !Setting::where('key', 'cdata.no_data_component')->exists()) {
            Setting::create([
                'group' => self::SETTINGS_GROUP,
                'key' => 'cdata.no_data_component',
                'config' => false,
                'name' => 'Configure External Integrations',
                'helper' => 'No external integrations are currently configured. To set up an external integration, click the "+ Driver" button.',
                'format'=> 'no-data',
                'hidden' => 0,
                'readonly' => 0,
                'ui' => null,
                'group_id' => $groupId,
            ]);
        }

        if ($groupId && !Setting::where('key', 'cdata.add_driver')->exists()) {
            Setting::create([
                'group' => self::SETTINGS_GROUP,
                'key' => 'cdata.add_driver',
                'config' => false,
                'name' => 'Driver',
                'helper' => null,
                'format'=> 'button',
                'hidden' => 1,
                'readonly' => 0,
                'ui' => '{"props":{"variant":"primary", "position": "top", "order":"100", "icon": "fas fa-plus"},"handler":"addCdataDriver"}',
                'group_id' => $groupId,
            ]);
        }
    }
};
