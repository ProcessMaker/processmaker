<?php
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use ProcessMaker\Model\ReportTable;
use ProcessMaker\Model\Process;

/**
 * Factory for a report table
 */
$factory->define(ReportTable::class, function (Faker $faker) {

    return [
        'name' => 'REPORT_TEST',
        'description' => $faker->sentence(3),
        'db_source_id' => null,
        'type' => 'NORMAL',
        'process_id' => function() {
            return factory(Process::class)->create()->id;
        }
    ];

});
