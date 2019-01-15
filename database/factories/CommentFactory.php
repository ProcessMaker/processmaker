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

    $model = factory($faker->randomElement([
        ProcessRequestToken::class,
        ProcessRequest::class,
    ]))->create();

    return [
        'user_id' => function () {
            return factory(User::class)->create()->getKey();
        },
        'commentable_id' => $model->getKey(),
        'commentable_type' => get_class($model),
        'subject' => $faker->sentence,
        'body' => $faker->sentence,
        'hidden' => $faker->randomElement([true, false]),
        'type' => $faker->randomElement(['LOG','MESSAGE']),
    ];
});
