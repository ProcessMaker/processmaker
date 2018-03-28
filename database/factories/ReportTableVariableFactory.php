<?php
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use ProcessMaker\Model\ReportTableVariable;

/**
 * Factory used to create report table variables
 */
$factory->define(ReportTableVariable::class, function (Faker $faker) {
    return [
        'FLD_UID' => str_replace('-', '', Uuid::uuid4()),
        'FLD_NAME' => $faker->word
    ];
});