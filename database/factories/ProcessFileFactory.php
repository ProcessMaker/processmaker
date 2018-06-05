<?php

use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;

/**
 * Model factory for a process file.
 */
$factory->define(\ProcessMaker\Model\ProcessFile::class, function (Faker $faker) {
    //was need base_path for complete to relative path.
    $filepath = $faker->file('/tmp', base_path('tests/shared/public'));
    return [
        'PRF_UID' => str_replace('-', '', Uuid::uuid4()),
        'process_id' => function () {
            $pro = factory(\ProcessMaker\Model\Process::class)->create();
            return $pro->id;
        },
        'USR_UID' => str_replace('-', '', Uuid::uuid4()),
        'PRF_UPDATE_USR_UID' => str_replace('-', '', Uuid::uuid4()),
        'PRF_PATH' => $filepath,
        'PRF_TYPE' => 'file',
        'PRF_DRIVE' => 'public',
        'PRF_PATH_FOR_CLIENT' => basename($filepath),
        'PRF_CREATE_DATE' => $faker->date(),
        'PRF_UPDATE_DATE' => $faker->date(),
    ];
});
