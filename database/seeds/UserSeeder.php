<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Model\User;
use ProcessMaker\Model\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(User::class)->create([
            'USR_USERNAME' => 'admin',
            'USR_PASSWORD' => Hash::make('admin'),
            'USR_FIRSTNAME' => 'admin',
            'USR_LASTNAME' => 'admin',
            'USR_TIME_ZONE' => 'UTC',
            'USR_ROLE' => 'PROCESSMAKER_ADMIN'
        ]);

    }
}
