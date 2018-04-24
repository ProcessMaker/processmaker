<?php
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use Illuminate\Support\Facades\Crypt;

/**
 * Model factory for an external Database
 */
$factory->define(\ProcessMaker\Model\Application::class, function (Faker $faker) {

    static $statuses = [
      1 => 'DRAFT',
      2 => 'TO_DO',
      3 => 'COMPLETED',
      4 => 'CANCELLED'
    ];

    // Get what our status will be
    $status = $faker->randomElement($statuses);
    $statusId = array_search($status, $statuses);
    $pin = $faker->regexify("[A-Z0-9]{4}");

    return [
        'APP_UID' => str_replace('-', '', Uuid::uuid4()),
        'APP_TITLE' => $faker->word,
        'APP_DESCRIPTION' => '',
        'APP_PARENT' => 0,  // 0 signifies no parent
        'APP_STATUS' => $status,
        'APP_STATUS_ID' => $statusId,
        'PRO_UID' => function () {
            return factory(\ProcessMaker\Model\Process::class)->create()->PRO_UID;
        },
        'APP_PROC_STATUS' => '',
        /**
         * @todo Determine if we need to put any other values in here
         */
        'APP_PROC_CODE' => '',
        'APP_PARALLEL' => 'N',
        'APP_INIT_USER' => function () {
            return factory(\ProcessMaker\Model\User::class)->create()->USR_UID;
        },
        'APP_CUR_USER' => function () {
            return factory(\ProcessMaker\Model\User::class)->create()->USR_UID;
        },
        'APP_INIT_DATE' => date('Y-m-d H:i:s'),
        'APP_PIN' => Crypt::encryptString($pin),
        'APP_DATA' => '[]' ];
});
