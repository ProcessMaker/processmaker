<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Model\User;
use ProcessMaker\Model\Role;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Group;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Create default All Users group
        factory(Group::class)->create([
            'uid' => Group::ALL_USERS_GROUP,
            'title' => 'Users',
            'status' => Group::STATUS_ACTIVE,
            'ux' => Group::UX_NORMAL
        ]);
        //Create admin user
        factory(User::class)->create([
            'username' => 'admin',
            'password' => Hash::make('admin'),
            'firstname' => 'admin',
            'lastname' => 'admin',
            'time_zone' => 'UTC'
        ]);

    }
}
