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
            'username' => 'admin',
            'password' => Hash::make('admin'),
            'firstname' => 'admin',
            'lastname' => 'admin',
            'time_zone' => 'UTC',
            'role_id' => Role::PROCESSMAKER_ADMIN
        ]);

    }
}
