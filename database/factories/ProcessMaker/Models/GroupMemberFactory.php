<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\User;

/**
 * Model factory for a Group
 */
class GroupMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'member_id' => function () {
                return User::factory()->create()->getKey();
            },
            'member_type' => User::class,
            'group_id' => function () {
                return Group::factory()->create()->getKey();
            },
        ];
    }
}
