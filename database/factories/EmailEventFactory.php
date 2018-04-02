<?php

use Faker\Generator as Faker;
use ProcessMaker\Model\EmailEvent;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ProcessFile;
use Ramsey\Uuid\Uuid;

/**
 * Model factory for a email event.
 */
$factory->define(EmailEvent::class, function (Faker $faker) {
    return [
        'EMAIL_EVENT_UID'     => str_replace('-', '', Uuid::uuid4()),
        'PRO_ID'              => function () {
            return factory(Process::class)->create()->PRO_ID;
        },
        //@todo create Event model
        'EVN_UID'             => str_replace('-', '', Uuid::uuid4()),
        'EMAIL_EVENT_FROM'    => $faker->email(),
        'EMAIL_EVENT_TO'      => $faker->email(),
        'EMAIL_EVENT_SUBJECT' => $faker->sentence(),
        'PRF_UID'             => function () {
            return factory(ProcessFile::class)->create()->PRF_UID;
        },
        'EMAIL_SERVER_UID'    => null,
    ];
});
