<?php

use ProcessMaker\Models\Setting;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class AddSessionControlSettings extends Upgrade
{
    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        $sessionControlOptions = [
            [
                [
                    'key' => 'session-control.ip_restriction',
                ],
                [
                    'format' => 'choice',
                    'config' => 0,
                    'name' => 'IP restriction',
                    'helper' => 'Restricts logins made by the same user from different IPs.',
                    'group' => Setting::SESSION_CONTROL_GROUP,
                    'hidden' => false,
                    'ui' => [
                        'order' => 10101,
                        'options' => [
                            'Disabled',
                            'Block duplicate session',
                            'Kill existing session',
                        ],
                    ],
                ],
            ],
            [
                [
                    'key' => 'session-control.device_restriction',
                ],
                [
                    'format' => 'choice',
                    'config' => 0,
                    'name' => 'Device restriction',
                    'helper' => 'Restricts logins made by the same user from different devices.',
                    'group' => Setting::SESSION_CONTROL_GROUP,
                    'hidden' => false,
                    'ui' => [
                        'order' => 10102,
                        'options' => [
                            'Disabled',
                            'Block duplicate session',
                            'Kill existing session',
                        ],
                    ],
                ],
            ],
        ];
        // Disable all mass assignable restrictions
        Setting::unguard();

        // Create settings
        foreach ($sessionControlOptions as $sessionControlOption) {
            Setting::firstOrCreate($sessionControlOption[0], $sessionControlOption[1]);
        }

        // Enable the mass assignment restrictions.
        Setting::reguard();
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        Setting::where('group', Setting::SESSION_CONTROL_GROUP)->delete();
    }
}
