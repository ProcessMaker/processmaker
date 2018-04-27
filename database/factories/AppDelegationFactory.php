<?php
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use Carbon\Carbon;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\Task;
use \ProcessMaker\Model\User;

$factory->define(\ProcessMaker\Model\Delegation::class, function (Faker $faker) {

    return [
        'APP_UID' => function () {
            return factory(Application::class)->create()->APP_UID;
        },
        'TAS_UID' => function () {
            return factory(Task::class)->create()->TAS_UID;
        },
        'PRO_UID' => function (array $task) {
            return Task::where('TAS_UID',$task['TAS_UID'])->first()->PRO_UID;
        },        
        'DEL_THREAD_STATUS' => $faker->randomElement(["OPEN"]),
        'DEL_LAST_INDEX' => $faker->randomElement(["0","1"]),
        'APP_NUMBER' => function (array $delegation) {
            return Application::where('APP_UID',$delegation['APP_UID'])->first()->APP_NUMBER;
        },
        'USR_UID' => function () {
              return factory(User::class)->create()->USR_UID;
          },
        'DEL_DELEGATE_DATE' => Carbon::now(),
        'DEL_DATA' => $faker->paragraph,
    ];
});
