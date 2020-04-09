<?php

use Faker\Generator as Faker;

use ProcessMaker\Models\Comment;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;

/**
 * Model factory for a Comment
 */
$factory->define(Comment::class, function (Faker $faker) {

    $model = $faker->randomElement([
        ProcessRequestToken::class,
        ProcessRequest::class,
    ]);

    return [
        'user_id' => function () {
            return factory(User::class)->create()->getKey();
        },
        'commentable_id' => factory($model),
        'commentable_type' => $model,
        'subject' => $faker->sentence,
        'body' => $faker->sentence,
        'hidden' => $faker->randomElement([true, false]),
        'type' => $faker->randomElement(['LOG','MESSAGE']),
    ];
});
