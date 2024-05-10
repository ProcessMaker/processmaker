<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\AnonymousUser;
use ProcessMaker\Models\User;

class AnonymousUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::updateOrCreate(
            ['username' => AnonymousUser::ANONYMOUS_USERNAME],
            [
                'firstname' => 'Anonymous',
                'lastname' => 'User',
                'email' => 'anonymous-pm4-user@processmaker.com',
                'status' => 'ACTIVE',
                'password' => Hash::make(bin2hex(random_bytes(16))),
                'timezone' => 'UTC',
            ]
        );

        $user->is_system = true;
        $user->save();
    }
}
