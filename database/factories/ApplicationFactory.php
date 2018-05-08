<?php
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;
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

    $maxNumber = \ProcessMaker\Model\Application::max('id') + 1;

    return [
        'APP_TITLE' => '#' . $faker->word,
        'APP_DESCRIPTION' => '',
        'APP_PARENT' => 0,  // 0 signifies no parent
        'APP_STATUS' => $status,
        'APP_STATUS_ID' => $statusId,
        'process_id' => factory(\ProcessMaker\Model\Process::class)->create()->id,
        'APP_PROC_STATUS' => '',
        /**
         * @todo Determine if we need to put any other values in here
         */
        'APP_PROC_CODE' => '',
        'APP_PARALLEL' => 'N',
        'creator_user_id' => factory(\ProcessMaker\Model\User::class)->create()->id,
        'current_user_id' => factory(\ProcessMaker\Model\User::class)->create()->id,
        'APP_INIT_DATE' => Carbon::now(),
        'APP_PIN' => Crypt::encryptString($pin),
        'APP_DATA' => '[]' ];
});
