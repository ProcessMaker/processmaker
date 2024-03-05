<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\InboxRule;
use ProcessMaker\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\ProcessMaker\Models\InboxRule>
 */
class InboxRuleFactory extends Factory
{
    protected $model = InboxRule::class;

    public function definition()
    {
        //Generates random saved_search_id and process_request_token_id even with null values
        //But never both null
        return [
            'name' => $this->faker->word,
            'user_id' => function () {
                return User::factory()->create()->id;
            },
            'active' => true,
            'end_date' => null,
            'saved_search_id' => null,
            'process_request_token_id' => null,
            'mark_as_priority' => false,
            'reassign_to_user_id' => null,
            'make_draft' => false,
            'submit_data' => false,
            'data' => json_encode(['key1' => 'value1', 'key2' => 'value2']),
        ];
    }
}
