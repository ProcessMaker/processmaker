<?php
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use ProcessMaker\Model\PmTable;

/**
 * Model factory for an additional table (pmTable)
 */
$factory->define(PmTable::class, function (Faker $faker) {
    $tableName = "TestPmTable";
    $pmTable = [
        'ADD_TAB_UID' => str_replace('-', '', Uuid::uuid4()),
        'ADD_TAB_NAME' => $tableName,
        'ADD_TAB_DESCRIPTION' =>$faker->sentence(3),
        'DBS_UID' => 'workflow',
        'ADD_TAB_TYPE' => 'NORMAL',
        'ADD_TAB_PLG_UID' => null,
        'ADD_TAB_GRID' => null,
        'ADD_TAB_TAG' => null,
        'PRO_UID' => null
    ];

    return $pmTable;
});
