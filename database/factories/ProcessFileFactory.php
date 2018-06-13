<?php

use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ProcessFile;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;

/**
 * Model factory for a process file.
 */
$factory->define(ProcessFile::class, function (Faker $faker) {
    //was need base_path for complete to relative path.
    $filePath = $faker->file('/tmp', base_path('tests/shared/public'));
    return [
        'uid' => Uuid::uuid4(),
        'process_id' => function () {
            return factory(Process::class)->create()->id;
        },
        'user_id' => function () {
            return factory(User::class)->create([
                'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
            ])->id;
        },
        'update_user_id' => function () {
            return factory(User::class)->create([
                'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
            ])->id;
        },
        'path' => $filePath,
        'type' => 'file',
        'drive' => 'public',
        'path_for_client' => basename($filePath),
    ];
});
