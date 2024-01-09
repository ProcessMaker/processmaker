<?php

use ProcessMaker\Models\Setting;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class AddPasswordPoliciesSettings extends Upgrade
{
    /**
     * Run the upgrades.
     */
    public function up(): void
    {
        $helperFailed = 'Number of consecutive unsuccessful login attempts before block the login action momentarily.';
        $passwordPolicies = [
            [
                [
                    'key' => 'password-policies.users_can_change',
                ],
                [
                    'format' => 'boolean',
                    'config' => true,
                    'name' => 'Password set by user',
                    'helper' => 'Allow to users to change their own password.',
                    'group' => Setting::LOGIN_OPTIONS_GROUP,
                    'hidden' => false,
                    'ui' => [
                        'order' => 10001,
                        'deleteSettingEnabled' => false,
                        'copySettingEnabled' => false,
                    ],
                ],
            ],
            [
                [
                    'key' => 'password-policies.numbers',
                ],
                [
                    'format' => 'boolean',
                    'config' => true,
                    'name' => 'Numeric characters',
                    'helper' => 'Passwords must contain minimum one numeric character.',
                    'group' => Setting::LOGIN_OPTIONS_GROUP,
                    'hidden' => false,
                    'ui' => [
                        'order' => 10002,
                        'deleteSettingEnabled' => false,
                        'copySettingEnabled' => false,
                    ],
                ],
            ],
            [
                [
                    'key' => 'password-policies.uppercase',
                ],
                [
                    'format' => 'boolean',
                    'config' => true,
                    'name' => 'Uppercase characters',
                    'helper' => 'Passwords must contain minimum one uppercase character.',
                    'group' => Setting::LOGIN_OPTIONS_GROUP,
                    'hidden' => false,
                    'ui' => [
                        'order' => 10003,
                        'deleteSettingEnabled' => false,
                        'copySettingEnabled' => false,
                    ],
                ],
            ],
            [
                [
                    'key' => 'password-policies.special',
                ],
                [
                    'format' => 'boolean',
                    'config' => true,
                    'name' => 'Special characters',
                    'helper' => 'Passwords must contain minimum one special character.',
                    'group' => Setting::LOGIN_OPTIONS_GROUP,
                    'hidden' => false,
                    'ui' => [
                        'order' => 10004,
                        'deleteSettingEnabled' => false,
                        'copySettingEnabled' => false,
                    ],
                ],
            ],
            [
                [
                    'key' => 'password-policies.maximum_length',
                ],
                [
                    'format' => 'text',
                    'config' => null,
                    'name' => 'Maximum length',
                    'helper' => 'Maximum password length allowed.',
                    'group' => Setting::LOGIN_OPTIONS_GROUP,
                    'hidden' => false,
                    'ui' => [
                        'order' => 10005,
                        'deleteSettingEnabled' => false,
                        'copySettingEnabled' => false,
                    ],
                ],
            ],
            [
                [
                    'key' => 'password-policies.minimum_length',
                ],
                [
                    'format' => 'text',
                    'config' => 8,
                    'name' => 'Minimum length',
                    'helper' => 'Minimum password length allowed.',
                    'group' => Setting::LOGIN_OPTIONS_GROUP,
                    'hidden' => false,
                    'ui' => [
                        'order' => 10006,
                        'deleteSettingEnabled' => false,
                        'copySettingEnabled' => false,
                    ],
                ],
            ],
            [
                [
                    'key' => 'password-policies.expiration_days',
                ],
                [
                    'format' => 'text',
                    'config' => null,
                    'name' => 'Password expiration',
                    'helper' => 'Password will expire in the days configured here.',
                    'group' => Setting::LOGIN_OPTIONS_GROUP,
                    'hidden' => false,
                    'ui' => [
                        'order' => 10007,
                        'deleteSettingEnabled' => false,
                        'copySettingEnabled' => false,
                    ],
                ],
            ],
            [
                [
                    'key' => 'password-policies.login_attempts',
                ],
                [
                    'format' => 'text',
                    'config' => 5,
                    'name' => 'Login failed',
                    'helper' => $helperFailed,
                    'group' => Setting::LOGIN_OPTIONS_GROUP,
                    'hidden' => false,
                    'ui' => [
                        'order' => 10008,
                        'isNotEmpty' => true,
                        'deleteSettingEnabled' => false,
                        'copySettingEnabled' => false,
                    ],
                ],
            ],
            [
                [
                    'key' => 'password-policies.2fa_enabled',
                ],
                [
                    'format' => 'boolean',
                    'config' => false,
                    'name' => 'Require Two Step Authentication',
                    'helper' => 'Enhance security with an additional authentication step for user verification.',
                    'group' => Setting::LOGIN_OPTIONS_GROUP,
                    'hidden' => false,
                    'ui' => [
                        'order' => 10009,
                    ],
                ],
            ],
            [
                [
                    'key' => 'password-policies.2fa_method',
                ],
                [
                    'format' => 'checkboxes',
                    'config' => [],
                    'name' => 'Two Step Authentication Method',
                    'helper' => 'A security code will be sent to all selected methods.',
                    'group' => Setting::LOGIN_OPTIONS_GROUP,
                    'hidden' => false,
                    'ui' => [
                        'order' => 10010,
                        'options' => [
                            'By email',
                            'By message to phone number',
                            'Authenticator App',
                        ],
                        'deleteSettingEnabled' => false,
                        'copySettingEnabled' => false,
                        'testSettingEndpoint' => '2fa/test',
                    ],
                ],
            ],
        ];

        // Disable all mass assignable restrictions
        Setting::unguard();

        // Create settings
        foreach ($passwordPolicies as $passwordPolicy) {
            Setting::firstOrCreate($passwordPolicy[0], $passwordPolicy[1]);
        }

        // Enable the mass assignment restrictions.
        Setting::reguard();
    }

    /**
     * Reverse the upgrades.
     */
    public function down(): void
    {
        Setting::where('group', Setting::LOGIN_OPTIONS_GROUP)->delete();
    }
}
