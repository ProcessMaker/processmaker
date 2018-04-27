<?php
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use Illuminate\Support\Facades\Crypt;

/**
 * Model factory for an external Database
 */
$factory->define(\ProcessMaker\Model\Application::class, function (Faker $faker) {
    static $statuses = ['DRAFT', 'TO_DO', 'COMPLETED', 'CANCELLED'];

    // Get what our status will be
    $status = $faker->randomElement($statuses);
    $statusId = array_search($status, $statuses) + 1;

    // Generate our related process
    $now = \Carbon\Carbon::now();


    $pin = $faker->regexify("[A-Z0-9]{4}");

    $maxNumber = \ProcessMaker\Model\Application::max('id') + 1;

    return [
        'APP_TITLE' => '#' . $maxNumber,
        'APP_DESCRIPTION' => '',
        'APP_PARENT' => 0,  // 0 signifies no parent
        'APP_STATUS' => $status,
        'APP_STATUS_ID' => $statusId,
        'process_id' => function () {
            return factory(\ProcessMaker\Model\Process::class)->create()->id;
        },
        'APP_PROC_STATUS' => '',
        /**
         * @todo Determine if we need to put any other values in here
         */
        'APP_PROC_CODE' => '',
        'APP_PARALLEL' => 'N',
        'creator_user_id' => function () {
            return factory(\ProcessMaker\Model\User::class)->create()->id;
        },
        'current_user_id' => function () {
            return factory(\ProcessMaker\Model\User::class)->create()->id;
        },
        'APP_INIT_DATE' => $now,
        'APP_PIN' => Crypt::encryptString($pin),
        'APP_DATA' => json_encode(['APP_NUMBER' => $maxNumber, 'PIN' => $pin]) ];
});
