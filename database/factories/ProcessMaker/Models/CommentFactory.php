<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;

/**
 * Model factory for a Comment
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $model = $this->faker->randomElement([
            ProcessRequestToken::class,
            ProcessRequest::class,
        ]);

        return [
            'user_id' => function () {
                return User::factory()->create()->getKey();
            },
            'commentable_id' => $model::factory(),
            'commentable_type' => $model,
            'subject' => $this->faker->sentence,
            'body' => $this->faker->sentence,
            'hidden' => $this->faker->randomElement([true, false]),
            'type' => $this->faker->randomElement(['LOG', 'MESSAGE']),
        ];
    }
}
