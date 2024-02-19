<?php

namespace Database\Factories\ProcessMaker\Models;

use ProcessMaker\Models\InboxRule;
use Illuminate\Database\Eloquent\Factories\Factory;

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
        $savedSearchId = $this->faker->boolean(50) ? $this->faker->numberBetween(1, 20) : null;
        $processRequestTokenId = $savedSearchId === null ? $this->faker->numberBetween(1, 20) : null;
        return [
            'name' => $this->faker->word,
            'active' => $this->faker->boolean,
            'end_date' => $this->faker->dateTimeBetween('now', '+1 year'),
            'saved_search_id' => $savedSearchId,
            'process_request_token_id' => $processRequestTokenId,
            'mark_as_priority' => $this->faker->boolean,
            'reasign_to_user_id' => null,
            'fill_data' => $this->faker->boolean,
            'submit_data' => json_encode(['key1' => 'value1', 'key2' => 'value2']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
