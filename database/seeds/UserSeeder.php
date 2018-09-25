<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;

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
        $group_uuid = factory(Group::class)->create([
            'name' => 'Users',
            'status' => 'ACTIVE'
        ])->uuid;
        //Create admin user
        $user_uuid = factory(User::class)->create([
            'username' => 'admin',
            'password' => Hash::make('admin'),
            'firstname' => 'admin',
            'lastname' => 'admin',
            'timezone' => 'UTC',
            'status' => 'ACTIVE'
        ])->uuid;

        factory(GroupMember::class)->create([
          'member_uuid' => $user_uuid,
          'member_type' => User::class,
          'group_uuid' => $group_uuid,
        ]);

    }
}
