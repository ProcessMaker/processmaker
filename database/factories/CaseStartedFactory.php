<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\CaseStarted;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\ProcessMaker\Models\CaseStarted>
 */
class CaseStartedFactory extends Factory
{
    protected $model = CaseStarted::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_number' => fake()->unique()->randomNumber(),
            'user_id' => fake()->randomElement([1, 3]),
            'case_title' => fake()->words(3, true),
            'case_title_formatted' => fake()->words(3, true),
            'case_status' => fake()->randomElement(['in_progress', 'completed']),
            'processes' => array_map(function() {
                return [
                    'id' => fake()->randomNumber(),
                    'name' => fake()->words(2, true),
                ];
            }, range(1, 3)),
            'requests' => [
                [
                    'id' => fake()->randomNumber(),
                    'name' => fake()->words(2, true),
                    'parent_request' => fake()->randomNumber(),
                ],
                [
                    'id' => fake()->randomNumber(),
                    'name' => fake()->words(3, true),
                    'parent_request' => fake()->randomNumber(),
                ],
            ],
            'request_tokens' => fake()->randomElement([fake()->randomNumber(), fake()->randomNumber(), fake()->randomNumber()]),
            'tasks' => [
                [
                    'id' => fake()->numerify('node_####'),
                    'name' => fake()->words(4, true),
                ],
                [
                    'id' => fake()->numerify('node_####'),
                    'name' => fake()->words(3, true),
                ],
                [
                    'id' => fake()->numerify('node_####'),
                    'name' => fake()->words(2, true),
                ],
            ],
            'participants' => [
                [
                    'id' => fake()->randomNumber(),
                    'name' => fake()->name(),
                ],
                [
                    'id' => fake()->randomNumber(),
                    'name' => fake()->name(),
                ],
                [
                    'id' => fake()->randomNumber(),
                    'name' => fake()->name(),
                ],
            ],
            'initiated_at' => fake()->dateTime(),
            'completed_at' => fake()->dateTime(),
            'keywords' => '',
        ];
    }
}
