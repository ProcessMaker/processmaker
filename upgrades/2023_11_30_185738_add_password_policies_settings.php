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
                    'group' => Setting::PASSWORD_POLICIES_GROUP,
                    'hidden' => false,
                    'ui' => [
                        'order' => 10001,
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
                    'group' => Setting::PASSWORD_POLICIES_GROUP,
                    'hidden' => false,
                    'ui' => [
                        'order' => 10002,
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
                    'group' => Setting::PASSWORD_POLICIES_GROUP,
                    'hidden' => false,
                    'ui' => [
                        'order' => 10003,
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
                    'group' => Setting::PASSWORD_POLICIES_GROUP,
                    'hidden' => false,
                    'ui' => [
                        'order' => 10004,
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
                    'group' => Setting::PASSWORD_POLICIES_GROUP,
                    'hidden' => false,
                    'ui' => [
                        'order' => 10005,
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
                    'group' => Setting::PASSWORD_POLICIES_GROUP,
                    'hidden' => false,
                    'ui' => [
                        'order' => 10006,
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
                    'group' => Setting::PASSWORD_POLICIES_GROUP,
                    'hidden' => false,
                    'ui' => [
                        'order' => 10007,
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
                    'group' => Setting::PASSWORD_POLICIES_GROUP,
                    'hidden' => false,
                    'ui' => [
                        'order' => 10008,
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
        Setting::where('group', Setting::PASSWORD_POLICIES_GROUP)->delete();
    }
}
