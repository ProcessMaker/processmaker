<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Models\User;
use ProcessMaker\Models\AnonymousUser;
use Illuminate\Support\Facades\Hash;

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
            ]
        );

        $user->is_system = true;
        $user->save();
    }
}
