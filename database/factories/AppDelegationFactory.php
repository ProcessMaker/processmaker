<?php
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use Carbon\Carbon;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\Task;
use \ProcessMaker\Model\User;

$factory->define(\ProcessMaker\Model\Delegation::class, function (Faker $faker) {

    return [
        'application_id' => factory(Application::class)->create()->id,
        'task_id' => factory(Task::class)->create()->id,
       'DEL_THREAD_STATUS' => $faker->randomElement(["OPEN"]),
        'DEL_LAST_INDEX' => $faker->randomElement(["0","1"]),
       'user_id' => factory(User::class)->create()->id,
        'DEL_DELEGATE_DATE' => Carbon::now(),
        'DEL_DATA' => $faker->paragraph,
    ];
});
