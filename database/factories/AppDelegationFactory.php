<?php
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use Carbon\Carbon;
use ProcessMaker\Model\Task;

$factory->define(\ProcessMaker\Model\Delegation::class, function (Faker $faker) {

    $task = factory(Task::class)->create();

    return [
        'APP_UID' => function () {
              return factory(\ProcessMaker\Model\Application::class)->create()->APP_UID;
          },
        'PRO_UID' => $task->PRO_UID,
        'TAS_UID' => $task->TAS_UID,
        'USR_UID' => function () {
              return factory(\ProcessMaker\Model\User::class)->create()->USR_UID;
          },
        'DEL_DELEGATE_DATE' => Carbon::now(),
        'DEL_DATA' => $faker->paragraph,
    ];
});
