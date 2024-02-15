<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\ClientRepository;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\User;

class AdminTestUserSeeder extends Seeder
{
    public static $INSTALLER_ADMIN_TEST_USERNAME = 'admin_test';

    public static $INSTALLER_ADMIN_TEST_PASSWORD = 'admin';

    public static $INSTALLER_ADMIN_TEST_EMAIL = 'admin_test@processmaker.com';

    public static $INSTALLER_ADMIN_TEST_FIRSTNAME = 'Admin';

    public static $INSTALLER_ADMIN_TEST_LASTNAME = 'TestUser';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(ClientRepository $clients)
    {
        // Create admin user
        User::updateOrCreate([
            'username' => self::$INSTALLER_ADMIN_TEST_USERNAME,
            'is_administrator' => true,
        ], [
            'username' => self::$INSTALLER_ADMIN_TEST_USERNAME,
            'password' => Hash::make(self::$INSTALLER_ADMIN_TEST_PASSWORD),
            'email' => self::$INSTALLER_ADMIN_TEST_EMAIL,
            'firstname' => self::$INSTALLER_ADMIN_TEST_FIRSTNAME,
            'lastname' => self::$INSTALLER_ADMIN_TEST_LASTNAME,
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
    }
}
