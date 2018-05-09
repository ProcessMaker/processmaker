<?php
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use ProcessMaker\Model\ReportTableColumn;

/**
 * Factory used to create report table variables
 */
$factory->define(ReportTableColumn::class, function (Faker $faker) {
    return [
        'name' => $faker->word
    ];
});