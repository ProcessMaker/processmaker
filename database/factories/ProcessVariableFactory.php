<?php
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use ProcessMaker\Model\DbSource;
use ProcessMaker\Model\InputDocument;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ProcessVariable;

$factory->define(ProcessVariable::class, function (Faker $faker) {
    return [
        'uid' => Uuid::uuid4(),
        'process_id' => function () {
            return factory(Process::class)->create()->id;
        },
        'input_document_id' => function () {
            return factory(InputDocument::class)->create()->id;
        },
        'db_source_id' => function () {
            return factory(DbSource::class)->create()->id;
        },
        'name' => $faker->unique()->word,
        'field_type' => $faker->randomElement(ProcessVariable::VARIABLE_TYPES),
        'field_size' => $faker->randomNumber(2),
        'label' => $faker->word,
        'default' => $faker->word,
        'null' => $faker->boolean,
        'sql' => $faker->sentence(5),
        'accepted_values' => $faker->sentence(5),
    ];
});
