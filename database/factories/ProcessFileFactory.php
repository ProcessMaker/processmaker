<?php
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;

/**
 * Model factory for a process file.
 */
$factory->define(\ProcessMaker\Model\ProcessFile::class, function (Faker $faker) {
    $filepath = $faker->file('/tmp','tests/shared/public');
    return [
        'PRF_UID'            => str_replace('-', '', Uuid::uuid4()),
        'PRO_UID'            => function () {
            $pro = factory(\ProcessMaker\Model\Process::class)->create();
            return $pro->PRO_UID;
        },
        'USR_UID'            => str_replace('-', '', Uuid::uuid4()),
        'PRF_UPDATE_USR_UID' => str_replace('-', '', Uuid::uuid4()),
        'PRF_PATH'           => $filepath,
        'PRF_TYPE'            => 'file',
        'PRF_DRIVE'           => 'public',
        'PRF_PATH_FOR_CLIENT' => basename($filepath),
        'PRF_CREATE_DATE'    => $faker->date(),
        'PRF_UPDATE_DATE'    => $faker->date(),
    ];
});
