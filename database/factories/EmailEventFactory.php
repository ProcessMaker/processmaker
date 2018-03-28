<?php
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;

/**
 * Model factory for a email event.
 */
$factory->define(\ProcessMaker\Model\EmailEvent::class, function (Faker $faker) {
    return [
        'EMAIL_EVENT_UID'     => str_replace('-', '', Uuid::uuid4()),
        'PRJ_UID'             => function () {
            return factory(\ProcessMaker\Model\Project::class)->create()->PRJ_UID;
        },
        //@todo create Event model
        'EVN_UID'             => str_replace('-', '', Uuid::uuid4()),
        'EMAIL_EVENT_FROM'    => $faker->email(),
        'EMAIL_EVENT_TO'      => $faker->email(),
        'EMAIL_EVENT_SUBJECT' => $faker->sentence(),
        'PRF_UID'             => function () {
            return factory(\ProcessMaker\Model\ProcessFile::class)->create()->PRF_UID;
        },
        'EMAIL_SERVER_UID'    => null,
    ];
});
