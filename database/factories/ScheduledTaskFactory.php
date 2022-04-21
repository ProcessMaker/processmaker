<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ScheduledTask;

$factory->define(ScheduledTask::class, function (Faker $faker) {
    $token = factory(ProcessRequestToken::class)->make([]);

    return [
        'process_id' => function () use ($token) {
            $token->save();

            return $token->process->getKey();
        },
        'process_request_id' => function () use ($token) {
            $token->save();

            return $token->processRequest->getKey();
        },
        'process_request_token_id' => function () use ($token) {
            $token->save();

            return $token->getKey();
        },
        'type' => $faker->randomElement(['INTERMEDIATE_TIMER_EVENT', 'TIMER_START_EVENT', 'BOUNDARY_TIMER_EVENT']),
        'last_execution' => date('Y-m-d H:t:s'),
        'configuration' => '{}',
    ];
});
