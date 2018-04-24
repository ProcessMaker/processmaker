<?php
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use Carbon\Carbon;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\Task;

$factory->define(\ProcessMaker\Model\Delegation::class, function (Faker $faker) {

    $task = factory(Task::class)->create();
    $application = factory(Application::class)->create();

    return [
        'APP_UID' => $application->APP_UID,
        'PRO_UID' => $task->PRO_UID,
        'TAS_UID' => $task->TAS_UID,
        'DEL_THREAD_STATUS' => $faker->randomElement(["OPEN"]),
        'DEL_LAST_INDEX' => $faker->randomElement(["0","1"]),
        'APP_NUMBER' => $application->APP_NUMBER,
        'USR_UID' => function () {
              return factory(\ProcessMaker\Model\User::class)->create()->USR_UID;
          },
        'DEL_DELEGATE_DATE' => Carbon::now(),
        'DEL_DATA' => $faker->paragraph,
    ];
});
