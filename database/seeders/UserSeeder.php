<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\ClientRepository;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\User;

class UserSeeder extends Seeder
{
    public static $INSTALLER_ADMIN_USERNAME = 'admin';

    public static $INSTALLER_ADMIN_PASSWORD = 'admin';

    public static $INSTALLER_ADMIN_EMAIL = 'admin@processmaker.com';

    public static $INSTALLER_ADMIN_FIRSTNAME = 'Admin';

    public static $INSTALLER_ADMIN_LASTNAME = 'User';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(ClientRepository $clients)
    {
        //Create admin user
        $user = User::updateOrCreate([
            'username' => self::$INSTALLER_ADMIN_USERNAME,
            'is_administrator' => true,
        ], [
            'username' => self::$INSTALLER_ADMIN_USERNAME,
            'password' => Hash::make(env('INSTALL_ADMIN_PASSWORD', self::$INSTALLER_ADMIN_PASSWORD)),
            'email' => self::$INSTALLER_ADMIN_EMAIL,
            'firstname' => self::$INSTALLER_ADMIN_FIRSTNAME,
            'lastname' => self::$INSTALLER_ADMIN_LASTNAME,
            'address' => null,
            'city' => null,
            'state' => null,
            'postal' => null,
            'country' => null,
            'phone' => null,
            'fax' => null,
            'cell' => null,
            'title' => null,
            'birthdate' => null,
            'timezone' => 'America/Los_Angeles',
            'datetime_format' => 'm/d/Y H:i',
            'language' => 'en',
            'status' => 'ACTIVE',
        ]);

        // Create client so we can generate tokens
        $clients->createPersonalAccessGrantClient('PmApi');

        // Create client OAuth (for 3-legged auth) - Authorization Code Grant for Swagger UI
        $clients->createAuthorizationCodeGrantClient(
            'Swagger UI Auth',
            [env('APP_URL', 'http://localhost') . '/api/oauth2-callback'],
            true, // confidential
            null, // user (system client)
            false // enableDeviceFlow
        );

        // Allow users get at token using the password grant flow
        $clients->createPasswordGrantClient(
            'Password Grant',
            null, // provider
            true // confidential (must be true as database requires secret to be NOT NULL)
        );
    }
}
