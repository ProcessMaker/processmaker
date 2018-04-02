<?php

use Faker\Generator as Faker;
use ProcessMaker\Model\Event;
use ProcessMaker\Model\Process;
use Ramsey\Uuid\Uuid;

/**
 * Model factory for an Event
 *
 */
$factory->define(Event::class, function (Faker $faker) {

    return [
        'EVN_UID'      => str_replace('-', '', Uuid::uuid4()),
        'EVN_NAME'     => $faker->sentence(3),
        'EVN_TYPE'     => $faker->randomElement([Event::TYPE_START, Event::TYPE_INTERMEDIATE, Event::TYPE_END]),
        'EVN_MARKER'   => $faker->randomElement([Event::MARKER_EMPTY, Event::MARKER_MESSAGETHROW, Event::MARKER_EMAIL, Event::MARKER_MESSAGECATCH]),
        'EVN_BEHAVIOR' => $faker->randomElement([Event::BEHAVIOR_THROW, Event::BEHAVIOR_CATCH]),
        'PRO_ID'       => function () {
            return factory(Process::class)->create()->PRO_ID;
        },
    ];
});
