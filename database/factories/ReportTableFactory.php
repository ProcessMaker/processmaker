<?php
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use ProcessMaker\Model\ReportTable;
use ProcessMaker\Model\Process;

/**
 * Factory for a report table
 */
$factory->define(ReportTable::class, function (Faker $faker) {
    // a new process is created
    $process = factory(Process::class)->create();

    $reportTable = [
        'ADD_TAB_UID' => str_replace('-', '', Uuid::uuid4()),
        'ADD_TAB_NAME' => 'REPORT_TEST',
        'ADD_TAB_DESCRIPTION' => $faker->sentence(3),
        'DBS_UID' => 'workflow',
        'ADD_TAB_TYPE' => 'NORMAL',
        'ADD_TAB_PLG_UID' => null,
        'ADD_TAB_GRID' => null,
        'ADD_TAB_TAG' => null,
        'PRO_UID' => $process->PRO_UID,
        'PRO_ID' => $process->PRO_ID
    ];

    return $reportTable;
});
