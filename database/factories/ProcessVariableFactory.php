<?php
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use ProcessMaker\Model\ProcessVariable;
use ProcessMaker\Model\Process;

$factory->define(ProcessVariable::class, function (Faker $faker) {
    return [
        'VAR_UID' => str_replace('-', '', Uuid::uuid4()),
        'PRO_ID' => function () {
            $pro = factory(Process::class)->create();
            return $pro->id;
        },
        'VAR_NAME' => $faker->unique()->word,
        'VAR_FIELD_TYPE' => $faker->randomElement(ProcessVariable::VARIABLE_TYPES),
        'VAR_FIELD_SIZE' => $faker->randomNumber(2),
        'VAR_LABEL' => $faker->word,
    ];
});
